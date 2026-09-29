<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingCapacityExceededException;
use App\Exceptions\CouponRejectedException;
use App\Exceptions\CouponUsageLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\SharedSessionCapacityExceededException;
use App\Http\Controllers\Concerns\GeneratesTimeSlots;
use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Product;
use App\Models\Room;
use App\Models\RoomPlan;
use App\Models\SaleItem;
use App\Models\SharedSession;
use App\Models\Workspace;
use App\Services\ActivityLogger;
use App\Services\AvailabilityService;
use App\Services\BusinessHoursService;
use App\Services\CouponService;
use App\Services\RoomPricingService;
use App\Services\SalesService;
use App\Support\Money;
use App\Support\Pricing\PriceQuote;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class BookingController extends Controller
{
    use GeneratesTimeSlots;

    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $ownerId = TenantContext::id();
        $status = $request->get('status');
        $date = $request->get('date');
        $roomId = $request->get('room_id');

        $bookings = Booking::where('owner_id', $ownerId)
            ->with(['room.workspace', 'hotspotUser'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($date, fn ($q) => $q->whereDate('booking_date', $date))
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate(15);

        $rooms = Room::where('owner_id', $ownerId)->get();

        return view('bookings.index', compact('bookings', 'rooms', 'status', 'date', 'roomId'));
    }

    public function create(Request $request): View
    {
        $ownerId = TenantContext::id();

        $rooms = Room::where('owner_id', $ownerId)
            ->where('is_available', true)
            ->with('workspace')
            ->get();

        $users = HotspotUser::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();

        $timeSlots = $this->generateTimeSlots();

        $selectedUserId = $request->get('hotspot_user_id');

        // Prefill from a click-to-book calendar link (room_id/booking_date/
        // start_time/end_time query params). These are only a starting
        // point for the form, never a booking guarantee — store() always
        // re-validates capacity server-side regardless of what's prefilled.
        $selectedRoomId = $request->get('room_id');
        $selectedDate = $request->get('booking_date');
        $selectedStartTime = $request->get('start_time');
        $selectedEndTime = $request->get('end_time');

        return view('bookings.create', compact(
            'rooms', 'users', 'timeSlots', 'selectedUserId',
            'selectedRoomId', 'selectedDate', 'selectedStartTime', 'selectedEndTime',
        ));
    }

    public function store(Request $request, AvailabilityService $availability, BusinessHoursService $businessHours, RoomPricingService $pricing): RedirectResponse|JsonResponse
    {
        $owner = TenantContext::user();

        // The quick-booking modal posts with Accept: application/json and needs
        // a JSON answer; the full booking page keeps its redirect-with-flash.
        $fail = fn (string $message) => $request->wantsJson()
            ? response()->json(['success' => false, 'message' => $message], 422)
            : back()->withInput()->with('error', $message);
        $ownerId = $owner->id;

        $rules = [
            'room_id' => 'required|exists:rooms,id',
            'hotspot_user_id' => 'required|exists:hotspot_users,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required_without:room_plan_id|nullable|date_format:H:i|after:start_time',
            'room_plan_id' => 'nullable|integer',
            'party_size' => 'nullable|integer|min:1',
            'guest_count' => 'nullable|integer|min:1|max:999',
            'amount_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ];

        // Web routes only render validation errors as JSON for api/* (bootstrap/app.php),
        // so the modal gets them explicitly; the form keeps the redirect-with-errors.
        if ($request->wantsJson()) {
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()->toArray()], 422);
            }
            $validated = $validator->validated();
        } else {
            $validated = $request->validate($rules);
        }

        $room = Room::where('id', $validated['room_id'])
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        // Shared rooms are advance-bookable (Phase 5) exactly like exclusive
        // ones — the capacity math below already handles both via
        // Room::effectiveCapacity(). What stays deferred is check-in/close
        // linkage and no-show automation (Phase 5c/5d), not the write itself.
        $hotspotUser = HotspotUser::where('id', $validated['hotspot_user_id'])
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        $partySize = (int) ($validated['party_size'] ?? 1);

        // People the price is quoted for. A shared room's party_size already
        // is its headcount (seats); an exclusive room's party_size stays 1
        // (it books the whole room), so its headcount travels separately.
        $people = $room->isShared() ? $partySize : (int) ($validated['guest_count'] ?? 1);
        [$quote, $planId, $planError] = $this->priceBooking($pricing, $room, $validated, $people);
        if ($planError) {
            return $fail($planError);
        }

        // Checked after pricing: a Custom Plan decides the booking's own window.
        if (! $businessHours->isWithinWorkingHours($owner, $validated['booking_date'], $validated['start_time'], $validated['end_time'])) {
            return $fail(__('app.booking.outside_working_hours'));
        }

        // Shared rooms are billed at checkout (SharedSessionController::
        // close()) once the actual elapsed time is known — their total here
        // is only a preview, so no deposit is accepted against it, no
        // matter what the client submitted. The payment section is already
        // hidden for shared rooms in the UI; this is the server-side
        // enforcement of that same rule.
        $amountPaid = $room->isShared() ? 0.0 : round((float) ($validated['amount_paid'] ?? 0), 2);

        if ($amountPaid > $quote->totalPrice) {
            return $fail(__('app.booking.payment.exceeds_total', ['total' => number_format($quote->totalPrice, 2)]));
        }

        try {
            $booking = DB::transaction(function () use ($validated, $ownerId, $room, $hotspotUser, $quote, $planId, $people, $availability, $partySize, $amountPaid) {
                // Lock the room row so concurrent store()/update() calls for
                // this room serialize through here. Genuine row-level locking
                // on MySQL (production) — compiles to a real `FOR UPDATE`; a
                // documented no-op on SQLite (dev/test). The exceedsCapacity()
                // check below is the second, engine-independent layer this
                // doesn't rely on alone: it re-verifies the actual committed
                // state after writing, so correctness doesn't hinge solely on
                // the lock having been honored.
                $lockedRoom = Room::where('id', $room->id)->lockForUpdate()->firstOrFail();

                $remaining = $availability->availabilityForRange(
                    $lockedRoom, $validated['booking_date'], $validated['start_time'], $validated['end_time'],
                );

                if ($remaining < $partySize) {
                    return null;
                }

                $newBooking = Booking::create([
                    'owner_id' => $ownerId,
                    'room_id' => $lockedRoom->id,
                    'room_plan_id' => $planId,
                    'hotspot_user_id' => $hotspotUser->id,
                    'party_size' => $partySize,
                    'booking_date' => $validated['booking_date'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'guest_count' => $lockedRoom->isShared() ? null : $people,
                    'price_per_hour' => $quote->ratePerHour,
                    'total_hours' => $quote->totalHours(),
                    'total_price' => $quote->totalPrice,
                    'pricing_note' => $quote->note,
                    'amount_paid' => $amountPaid,
                    'payment_status' => Booking::derivePaymentStatus($amountPaid, $quote->totalPrice),
                    'status' => 'confirmed',
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($availability->exceedsCapacity($lockedRoom, $validated['booking_date'], $validated['start_time'], $validated['end_time'])) {
                    throw new BookingCapacityExceededException;
                }

                return $newBooking;
            });
        } catch (BookingCapacityExceededException) {
            $booking = null;
        }

        if (! $booking) {
            return $fail('This room is already booked for the selected time slot. Please choose a different time.');
        }

        $paidNote = $amountPaid > 0 ? " (paid {$amountPaid})" : '';
        $this->activityLogger->log('booking.created', $booking, "Booked {$booking->room->name} for {$booking->booking_date->format('M d, Y')}{$paidNote}");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'booking_id' => $booking->id,
                'url' => "/bookings/{$booking->id}",
                'message' => __('app.quick_booking.booked', ['name' => $hotspotUser->name, 'total' => Money::format((float) $booking->total_price)]),
            ]);
        }

        return redirect("/bookings/{$booking->id}")->with('success', 'Booking confirmed successfully.');
    }

    /**
     * Price a booking through RoomPricingService: a Custom Plan when one is
     * chosen (fixed price, and the plan sets the booking's window — so
     * $validated's start/end are replaced by the plan's), otherwise the
     * room's standard pricing. Returns [quote, planId, errorMessage].
     *
     * @return array{0: ?PriceQuote, 1: ?int, 2: ?string}
     */
    private function priceBooking(RoomPricingService $pricing, Room $room, array &$validated, int $people): array
    {
        if (empty($validated['room_plan_id'])) {
            if (empty($validated['end_time'])) {
                return [null, null, __('validation.required', ['attribute' => 'end time'])];
            }

            return [$pricing->quoteBooking($room, $people, $validated['booking_date'], $validated['start_time'], $validated['end_time']), null, null];
        }

        $plan = RoomPlan::where('id', $validated['room_plan_id'])
            ->where('owner_id', $room->owner_id)
            ->where('room_id', $room->id)
            ->first();
        if (! $plan) {
            return [null, null, __('app.plans.errors.plan_room')];
        }

        try {
            $quote = $pricing->quotePlan($room, $plan, $people, $validated['booking_date'], $validated['start_time']);
        } catch (\InvalidArgumentException $e) {
            return [null, null, __('app.plans.errors.'.$e->getMessage(), ['count' => $plan->people])];
        }

        $window = $pricing->planWindow($room, $plan, $validated['booking_date'], $validated['start_time']);
        $validated['start_time'] = $window['start'];
        $validated['end_time'] = $window['end'];

        return [$quote, $plan->id, null];
    }

    /**
     * The room's Custom Plans for the quote UI: each with its fixed price, the
     * window it would book from the chosen start, whether it fits the people
     * count, and whether that window is free and within working hours.
     */
    private function planOptions(Room $room, array $validated, ?int $bookingId, int $people, AvailabilityService $availability, BusinessHoursService $businessHours, $owner, RoomPricingService $pricing): array
    {
        return $room->plans->map(function (RoomPlan $plan) use ($room, $validated, $bookingId, $people, $availability, $businessHours, $owner, $pricing) {
            $window = $pricing->planWindow($room, $plan, $validated['booking_date'], $validated['start_time']);
            $state = 'free';
            if (! $window || $window['minutes'] <= 0) {
                $state = 'no_fit';
            } elseif (! $businessHours->isWithinWorkingHours($owner, $validated['booking_date'], $window['start'], $window['end'])) {
                $state = 'outside_hours';
            } elseif ($availability->availabilityForRange($room, $validated['booking_date'], $window['start'], $window['end'], $bookingId) < ($room->isShared() ? $plan->people : 1)) {
                $state = 'booked';
            }

            return [
                'id' => $plan->id,
                'name' => $plan->displayName(),
                'note' => $plan->note(),
                'people' => $plan->people,
                'people_label' => $plan->peopleLabel(),
                'duration_label' => $plan->durationLabel(),
                'price' => (float) $plan->price,
                'price_display' => Money::format((float) $plan->price),
                'start_time' => $window['start'] ?? null,
                'end_time' => $window['end'] ?? null,
                'fits_people' => $people === $plan->people,
                'state' => $state,
            ];
        })->values()->all();
    }

    public function show($id): View
    {
        $owner = TenantContext::user();

        $booking = Booking::where('owner_id', $owner->id)
            ->with(['room.workspace', 'hotspotUser', 'sale.items'])
            ->findOrFail($id);

        // Catalog for the "add items" picker — only relevant when the sales feature is on.
        $products = $owner->hasFeature('sales')
            ? Product::where('owner_id', $owner->id)->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('bookings.show', compact('booking', 'products'));
    }

    public function edit($id): View
    {
        $ownerId = TenantContext::id();

        $booking = Booking::where('owner_id', $ownerId)
            ->with(['room.workspace', 'hotspotUser'])
            ->findOrFail($id);

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return redirect("/bookings/{$id}")->with('error', 'Only pending or confirmed bookings can be edited.');
        }

        $rooms = Room::where('owner_id', $ownerId)
            ->where('is_available', true)
            ->with('workspace')
            ->get();

        $users = HotspotUser::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();

        $timeSlots = $this->generateTimeSlots();

        return view('bookings.edit', compact('booking', 'rooms', 'users', 'timeSlots'));
    }

    public function update(Request $request, $id, AvailabilityService $availability, BusinessHoursService $businessHours, RoomPricingService $pricing, CouponService $coupons): RedirectResponse
    {
        $owner = TenantContext::user();
        $ownerId = $owner->id;

        $booking = Booking::where('owner_id', $ownerId)->findOrFail($id);

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Only pending or confirmed bookings can be edited.');
        }

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'hotspot_user_id' => 'required|exists:hotspot_users,id',
            'booking_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required_without:room_plan_id|nullable|date_format:H:i|after:start_time',
            'room_plan_id' => 'nullable|integer',
            'party_size' => 'nullable|integer|min:1',
            'guest_count' => 'nullable|integer|min:1|max:999',
            'amount_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $room = Room::where('id', $validated['room_id'])
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        $partySize = (int) ($validated['party_size'] ?? 1);
        $people = $room->isShared() ? $partySize : (int) ($validated['guest_count'] ?? $booking->guest_count ?? 1);
        [$quote, $planId, $planError] = $this->priceBooking($pricing, $room, $validated, $people);
        if ($planError) {
            return back()->withInput()->with('error', $planError);
        }

        if (! $businessHours->isWithinWorkingHours($owner, $validated['booking_date'], $validated['start_time'], $validated['end_time'])) {
            return back()->withInput()->with('error', __('app.booking.outside_working_hours'));
        }

        // Falls back to whatever was already recorded so editing the time/
        // room doesn't silently wipe a deposit the request didn't mention —
        // but it's still re-validated against the *new* total below, since
        // shortening the booking could make an old deposit exceed it.
        $amountPaid = $room->isShared() ? 0.0 : round((float) ($validated['amount_paid'] ?? $booking->amount_paid), 2);

        if ($amountPaid > $quote->totalPrice) {
            return back()->withInput()->with('error', __('app.booking.payment.exceeds_total', ['total' => number_format($quote->totalPrice, 2)]));
        }

        try {
            $updated = DB::transaction(function () use ($validated, $room, $booking, $quote, $planId, $people, $availability, $id, $partySize, $amountPaid) {
                // Same lock + post-write re-verify pattern as store() — see
                // the comment there for why both layers exist.
                $lockedRoom = Room::where('id', $room->id)->lockForUpdate()->firstOrFail();

                $remaining = $availability->availabilityForRange(
                    $lockedRoom, $validated['booking_date'], $validated['start_time'], $validated['end_time'],
                    excludeBookingId: (int) $id,
                );

                if ($remaining < $partySize) {
                    return false;
                }

                $booking->update([
                    'room_id' => $lockedRoom->id,
                    'room_plan_id' => $planId,
                    'hotspot_user_id' => $validated['hotspot_user_id'],
                    'party_size' => $partySize,
                    'booking_date' => $validated['booking_date'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'guest_count' => $lockedRoom->isShared() ? null : $people,
                    'price_per_hour' => $quote->ratePerHour,
                    'total_hours' => $quote->totalHours(),
                    'total_price' => $quote->totalPrice,
                    'pricing_note' => $quote->note,
                    'amount_paid' => $amountPaid,
                    'payment_status' => Booking::derivePaymentStatus($amountPaid, $quote->totalPrice),
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($availability->exceedsCapacity($lockedRoom, $validated['booking_date'], $validated['start_time'], $validated['end_time'])) {
                    throw new BookingCapacityExceededException;
                }

                return true;
            });
        } catch (BookingCapacityExceededException) {
            $updated = false;
        }

        if (! $updated) {
            return back()->withInput()->with('error',
                'This room is already booked for the selected time slot. Please choose a different time.');
        }

        $this->activityLogger->log('booking.updated', $booking, "Updated booking #{$booking->id}");

        // The room/time (and so total_price) may have just changed under an
        // already-attached coupon — re-evaluate against the fresh totals
        // rather than leave a stale discount_total on the books.
        $warning = $coupons->syncBooking($booking->fresh(['sale.items']));

        $redirect = redirect("/bookings/{$id}")->with('success', 'Booking updated successfully.');

        return $warning ? $redirect->with('warning', $warning) : $redirect;
    }

    public function updateStatus(Request $request, $id, CouponService $coupons): RedirectResponse
    {
        $booking = Booking::where('owner_id', TenantContext::id())->with('room')->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        // 'checked_in'/'no_show' are never reachable through this generic
        // endpoint (not in the validation whitelist above) — they only
        // happen through checkIn() and the no-show sweep, so the invariant
        // "checked_in ⟺ an open SharedSession exists for this booking"
        // can't be bypassed here.
        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        // A shared-room reservation must go through check-in to become
        // "completed" — its pricing only becomes real at that point. Direct
        // pending/confirmed -> completed here would freeze it at the
        // pre-arrival estimate and skip creating the session that's the
        // entire point of the reservation. Cancelling is still allowed.
        if ($booking->room->isShared() && $validated['status'] === 'completed') {
            return back()->with('error', 'Shared-room reservations must be checked in, not marked completed directly.');
        }

        if (! in_array($validated['status'], $validTransitions[$booking->status] ?? [])) {
            return back()->with('error', 'Invalid status transition.');
        }

        // The permission:bookings.edit,bookings.cancel route middleware lets
        // either grant reach this shared status-machine endpoint; only the
        // target status here tells you which capability actually applies.
        $staff = auth('staff')->user();
        $requiredPermission = $validated['status'] === 'cancelled' ? 'bookings.cancel' : 'bookings.edit';
        if ($staff && ! $staff->hasPermission($requiredPermission)) {
            return back()->with('permission_denied', __('app.msg.permission_denied'));
        }

        if ($validated['status'] === 'completed') {
            // The in-memory $booking->status check above is not itself a
            // race guard — this atomic conditional update is: only the
            // request that actually flips confirmed -> completed here goes
            // on to redeem any attached coupon, exactly once. A coupon
            // rejection (no longer valid, limit reached by a racing
            // completion, etc.) rolls back the whole transaction, so the
            // booking is left confirmed rather than silently completed with
            // a discount that was never actually granted.
            try {
                $claimed = DB::transaction(function () use ($booking, $coupons) {
                    $updated = Booking::where('id', $booking->id)
                        ->where('owner_id', $booking->owner_id)
                        ->where('status', 'confirmed')
                        ->update(['status' => 'completed']);

                    if ($updated) {
                        $coupons->redeemForBooking($booking->fresh(['sale.items']));
                    }

                    return (bool) $updated;
                });
            } catch (CouponRejectedException|CouponUsageLimitExceededException $e) {
                return back()->with('error', __('app.coupons.errors.cannot_complete', ['reason' => $e->getMessage()]));
            }

            if (! $claimed) {
                return back()->with('error', 'Invalid status transition.');
            }
        } else {
            $booking->update(['status' => $validated['status']]);
        }

        $booking->refresh();
        $action = $validated['status'] === 'cancelled' ? 'booking.cancelled' : 'booking.status_changed';
        $this->activityLogger->log($action, $booking, "Booking #{$booking->id} status changed to {$booking->statusLabel()}");

        return back()->with('success', 'Booking status updated to '.$booking->statusLabel().'.');
    }

    /**
     * Claim a still-open shared-room reservation into a live, billable
     * SharedSession. The actual headcount is entered here — it may be less
     * than the original party_size (a partial no-show); the gap is
     * recoverable via Booking::noShowSeats() rather than overwriting the
     * reservation's own party_size. Never available for exclusive rooms —
     * they have no check-in concept and keep their existing four-status
     * lifecycle untouched.
     */
    public function checkIn(Request $request, $id, AvailabilityService $availability, BusinessHoursService $businessHours, RoomPricingService $pricing): RedirectResponse
    {
        $owner = TenantContext::user();
        $ownerId = $owner->id;

        $booking = Booking::where('owner_id', $ownerId)->with('room')->findOrFail($id);

        if (! $booking->room->isShared()) {
            return back()->with('error', 'Only shared-room reservations can be checked in.');
        }

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'This reservation can no longer be checked in.');
        }

        // Live check, not a trust in the scheduled sweep: a reservation past
        // its grace period is effectively a no-show even if the periodic
        // sweep hasn't flipped its status yet.
        if ($booking->isPastNoShowGrace()) {
            return back()->with('error', 'This reservation has expired and can no longer be checked in.');
        }

        // Same live-instant check as SharedSessionController::store() — a
        // reservation's own working-hours validity was already checked at
        // booking time, but check-in can happen well after, so it's
        // re-evaluated against right now.
        if (! $businessHours->isOpenNow($owner)) {
            return back()->with('error', __('app.booking.outside_working_hours'));
        }

        $validated = $request->validate([
            'party_size' => 'required|integer|min:1',
        ]);
        $actualPartySize = (int) $validated['party_size'];

        try {
            $session = DB::transaction(function () use ($booking, $ownerId, $actualPartySize, $availability, $pricing) {
                $lockedRoom = Room::where('id', $booking->room_id)->lockForUpdate()->firstOrFail();

                // Atomic claim: only one concurrent check-in attempt on this
                // booking can win. If another request (or the no-show sweep)
                // already moved it off 'confirmed', this affects 0 rows.
                $claimed = Booking::where('id', $booking->id)
                    ->where('status', 'confirmed')
                    ->update(['status' => 'checked_in', 'checked_in_party_size' => $actualPartySize]);

                if ($claimed === 0) {
                    return null;
                }

                // Early check-in is allowed (the design decision), provided
                // the room has room right now — checked against the "right
                // now" formula with this booking's own (about to be
                // replaced) reserved seats excluded, so its old party_size
                // isn't double-counted against the actual headcount walking
                // in for it.
                $available = $availability->availableNow($lockedRoom, excludeBookingId: $booking->id);
                if ($actualPartySize > $available) {
                    throw new SharedSessionCapacityExceededException;
                }

                $now = now();
                // Snapshotted from the locked room now, at check-in time — same
                // reasoning as SharedSessionController::store()'s walk-in path.
                // Without this, a rate/billing-unit change made after check-in
                // would silently apply to this session's close-time bill.
                $newSession = SharedSession::create([
                    'owner_id' => $ownerId,
                    'room_id' => $lockedRoom->id,
                    'hotspot_user_id' => $booking->hotspot_user_id,
                    'party_size' => $actualPartySize,
                    'session_date' => $now->format('Y-m-d'),
                    'start_time' => $now->format('H:i'),
                    'opened_at' => $now,
                    'status' => 'open',
                    'booking_id' => $booking->id,
                    'billing_unit' => $lockedRoom->billing_unit,
                    'billed_price_per_hour' => $lockedRoom->price_per_hour,
                    'pricing_snapshot' => $pricing->snapshotFor($lockedRoom),
                    'plan_snapshot' => $pricing->planSnapshotFor($booking),
                ]);

                // Post-write defense-in-depth, same reasoning as store()'s.
                if ($availability->usedCapacityNow($lockedRoom) > $lockedRoom->effectiveCapacity()) {
                    throw new SharedSessionCapacityExceededException;
                }

                return $newSession;
            });
        } catch (SharedSessionCapacityExceededException) {
            return back()->with('error', 'Not enough seats available right now for that many people.');
        }

        if (! $session) {
            return back()->with('error', 'This reservation was already checked in or is no longer available.');
        }

        $this->activityLogger->log('booking.checked_in', $booking, "Checked in booking #{$booking->id}");

        return redirect()->route('active-sessions.index')
            ->with('success', 'Checked in. The session is now open.');
    }

    public function destroy($id): RedirectResponse
    {
        $booking = Booking::where('owner_id', TenantContext::id())->findOrFail($id);

        if ($booking->status !== 'cancelled') {
            return back()->with('error', 'Only cancelled bookings can be deleted.');
        }

        $booking->delete();

        return redirect('/bookings')->with('success', 'Booking deleted successfully.');
    }

    /**
     * Day view is the default landing state — "what does my space look like
     * right now." Week reuses day's per-room rendering for each of 7 days;
     * month keeps its existing grid. AvailabilityService is the only source
     * of booked/available data for day and week — this method only shapes
     * its output per room/day for the view, no capacity math of its own.
     */
    public function calendar(Request $request, AvailabilityService $availability): View
    {
        $ownerId = TenantContext::id();

        $view = $request->get('view', 'day');
        if (! in_array($view, ['day', 'week', 'month'], true)) {
            $view = 'day';
        }

        $date = $request->get('date', now()->format('Y-m-d'));
        $carbon = Carbon::parse($date);
        $roomId = $request->get('room_id');
        $workspaceId = $request->get('workspace_id');

        $workspaces = Workspace::where('owner_id', $ownerId)->orderBy('name')->get();

        // Unfiltered list for the filter dropdown, so picking a room doesn't
        // collapse the dropdown down to just that one option afterward.
        $allRooms = Room::where('owner_id', $ownerId)
            ->where('is_available', true)
            ->with('workspace')
            ->orderBy('name')
            ->get();

        $rooms = $allRooms
            ->when($workspaceId, fn ($c) => $c->where('workspace_id', (int) $workspaceId))
            ->when($roomId, fn ($c) => $c->where('id', (int) $roomId))
            ->values();

        $data = compact('carbon', 'date', 'view', 'rooms', 'allRooms', 'workspaces', 'roomId', 'workspaceId');

        if ($view === 'month') {
            $data['bookings'] = Booking::where('owner_id', $ownerId)
                ->with(['room.workspace', 'hotspotUser'])
                ->whereMonth('booking_date', $carbon->month)
                ->whereYear('booking_date', $carbon->year)
                ->where('status', '!=', 'cancelled')
                ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
                ->when($workspaceId, fn ($q) => $q->whereHas('room', fn ($rq) => $rq->where('workspace_id', $workspaceId)))
                ->get()
                ->groupBy(fn ($b) => $b->booking_date->format('Y-m-d'));
        } elseif ($view === 'week') {
            $startOfWeek = $carbon->copy()->startOfWeek(Carbon::MONDAY);
            $data['days'] = collect(range(0, 6))->map(function (int $i) use ($startOfWeek, $rooms, $availability) {
                $day = $startOfWeek->copy()->addDays($i);

                return [
                    'date' => $day,
                    'rooms' => $this->roomDayAvailability($rooms, $day->format('Y-m-d'), $availability),
                ];
            });
        } else {
            $data['dayRooms'] = $this->roomDayAvailability($rooms, $carbon->format('Y-m-d'), $availability);
        }

        return view('bookings.calendar', $data);
    }

    /**
     * Per-room booked/available blocks plus that day's actual bookings, for
     * the day and week views. Purely a packaging step around
     * AvailabilityService — no availability logic lives here or in the view.
     */
    private function roomDayAvailability($rooms, string $dateStr, AvailabilityService $availability): array
    {
        $isToday = $dateStr === now()->format('Y-m-d');

        return $rooms->map(fn (Room $room) => [
            'room' => $room,
            'date' => $dateStr,
            'blocks' => $availability->freeBusyForDay($room, $dateStr),
            'slots' => $availability->bookableSlots($room, $dateStr),
            'bookings' => $room->bookings()
                ->whereDate('booking_date', $dateStr)
                ->where('status', '!=', 'cancelled')
                ->with('hotspotUser')
                ->orderBy('start_time')
                ->get(),
            'live' => ($isToday && $room->isShared()) ? $availability->liveOccupancy($room) : null,
        ])->all();
    }

    /**
     * Standalone quick-lookup page: pick a room/date/time/party size and see
     * whether it's available, without going through the booking form. Pure
     * page shell — the actual answer comes from the same checkAvailability()
     * JSON endpoint the create/edit forms already call, so there is exactly
     * one place that decides availability, not a second copy for this page.
     */
    public function availabilityLookup(Request $request): View
    {
        $ownerId = TenantContext::id();

        $rooms = Room::where('owner_id', $ownerId)
            ->where('is_available', true)
            ->with('workspace')
            ->orderBy('name')
            ->get();

        $timeSlots = $this->generateTimeSlots();

        return view('bookings.availability', compact('rooms', 'timeSlots'));
    }

    public function checkAvailability(Request $request, AvailabilityService $availability, BusinessHoursService $businessHours, RoomPricingService $pricing): JsonResponse
    {
        $owner = TenantContext::user();

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'booking_id' => 'nullable|exists:bookings,id',
            'party_size' => 'nullable|integer|min:1',
            'guest_count' => 'nullable|integer|min:1|max:999',
        ]);

        $room = Room::where('id', $validated['room_id'])
            ->where('owner_id', $owner->id)
            ->firstOrFail();

        $partySize = (int) ($validated['party_size'] ?? 1);

        // Room-type-aware: previously this always ran the exclusive-room
        // conflict check regardless of type, which was silently wrong for a
        // shared room (blind to currently-open SharedSessions, only ever
        // seeing historical/completed bookings). availabilityForRange() uses
        // Room::effectiveCapacity(), so an exclusive room still behaves
        // exactly as before (any overlap => unavailable) while a shared
        // room now gets a real seat-remaining answer instead of a wrong one.
        $remaining = $availability->availabilityForRange(
            $room,
            $validated['booking_date'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['booking_id'] ?? null,
        );

        // This is the same read this preview's own store()/update() submit
        // will re-check — without it, a slot outside working hours would
        // show "Available" here and only fail once actually submitted.
        $withinHours = $businessHours->isWithinWorkingHours(
            $owner,
            $validated['booking_date'],
            $validated['start_time'],
            $validated['end_time'],
        );

        // An exclusive room is booked whole: it needs 1 "seat" however many
        // people come — the headcount only affects its price. A shared room
        // needs a seat per person.
        $seatsNeeded = $room->isShared() ? $partySize : 1;
        $isAvailable = $seatsNeeded <= $remaining && $withinHours;

        $quote = null;
        if ($isAvailable) {
            $people = $room->isShared() ? $partySize : (int) ($validated['guest_count'] ?? $partySize);
            $quote = $pricing->quoteBooking($room, $people, $validated['booking_date'], $validated['start_time'], $validated['end_time']);
        }

        return response()->json([
            'available' => $isAvailable,
            'remaining' => $remaining,
            'total_hours' => $quote?->totalHours(),
            'total_price' => $quote?->totalPrice,
            'price_per_hour' => $room->price_per_hour,
            'price_note' => $quote?->note,
            'price_summary' => $room->pricingSummary(),
        ]);
    }

    /**
     * Feeds the room-picker cards on the booking form: for every one of the
     * owner's rooms, the price at the currently-selected duration plus a
     * 3-state availability label — in one request instead of the picker
     * calling checkAvailability() once per room. Delegates to the exact same
     * RoomPricingService/AvailabilityService/BusinessHoursService calls
     * checkAvailability() itself uses; no pricing/availability math is
     * duplicated here. Read-only, no locks — safe to call on every keystroke.
     */
    public function roomOptions(Request $request, AvailabilityService $availability, BusinessHoursService $businessHours, RoomPricingService $pricing): JsonResponse
    {
        $owner = TenantContext::user();

        $validated = $request->validate([
            'booking_date' => 'required|date',
            'start_time' => 'nullable|required_with:end_time|date_format:H:i',
            'end_time' => 'nullable|required_with:start_time|date_format:H:i|after:start_time',
            'party_size' => 'nullable|integer|min:1',
            'guest_count' => 'nullable|integer|min:1|max:999',
            'booking_id' => 'nullable|exists:bookings,id',
        ]);

        // Date only (no time picked yet): just the day's Full Day window, so
        // the form can offer "Full day" before a start time exists.
        if (empty($validated['start_time'])) {
            $fullDay = $businessHours->fullDayWindow($owner, $validated['booking_date']);

            return response()->json([
                'rooms' => [],
                'full_day' => $fullDay ? ['start' => $fullDay['start'], 'end' => $fullDay['end']] : null,
            ]);
        }

        $bookingId = $validated['booking_id'] ?? null;
        $partySize = (int) ($validated['party_size'] ?? 1);
        $guestCount = (int) ($validated['guest_count'] ?? 1);

        // Same for every room at this date/time, so computed once rather
        // than once per room.
        $withinHours = $businessHours->isWithinWorkingHours(
            $owner,
            $validated['booking_date'],
            $validated['start_time'],
            $validated['end_time'],
        );

        $rooms = Room::where('owner_id', $owner->id)
            ->where('is_available', true)
            ->with(['workspace', 'plans'])
            ->orderBy('name')
            ->get();

        $options = $rooms->map(function (Room $room) use ($availability, $validated, $bookingId, $partySize, $guestCount, $withinHours, $pricing, $businessHours, $owner) {
            $remaining = $availability->availabilityForRange(
                $room,
                $validated['booking_date'],
                $validated['start_time'],
                $validated['end_time'],
                $bookingId,
            );

            // Priced regardless of availability, so an unavailable card can
            // still show what it would have cost.
            $quote = $pricing->quoteBooking(
                $room,
                $room->isShared() ? $partySize : $guestCount,
                $validated['booking_date'],
                $validated['start_time'],
                $validated['end_time'],
            );

            if (! $withinHours) {
                $state = 'unavailable';
                $reason = 'outside_hours';
            } elseif ($partySize > $remaining) {
                $state = 'unavailable';
                $reason = $room->isShared() ? 'no_seats' : 'conflict';
            } else {
                // "Booked elsewhere today" — a whole-day overlap scan (00:00–
                // 23:59), reusing usedCapacity() rather than freeBusyForDay()
                // since only usedCapacity() can exclude the booking being
                // edited via $bookingId.
                $bookedElsewhere = $availability->usedCapacity(
                    $room, $validated['booking_date'], '00:00', '23:59', $bookingId,
                ) > 0;

                $state = $bookedElsewhere ? 'partial' : 'free';
                $reason = null;
            }

            return [
                'id' => $room->id,
                'name' => $room->name,
                'type' => $room->type,
                'type_label' => $room->typeLabel(),
                'capacity' => $room->capacity,
                'is_shared' => $room->isShared(),
                'price_per_hour' => (float) $room->price_per_hour,
                'price_per_hour_display' => Money::format((float) $room->price_per_hour),
                'price_summary' => $room->pricingSummary(),
                'pricing_model' => $room->pricingRules()->model,
                'uses_people' => $room->pricingRules()->usesPeople(),
                'plans' => $this->planOptions($room, $validated, $bookingId, $room->isShared() ? $partySize : $guestCount, $availability, $businessHours, $owner, $pricing),
                'total_hours' => $quote->totalHours(),
                'total_price' => $quote->totalPrice,
                'total_price_display' => Money::format($quote->totalPrice),
                'price_note' => $quote->note,
                'state' => $state,
                'reason' => $reason,
            ];
        });

        // The date's business day, so the form can offer a one-tap "Full day"
        // duration that lands exactly on the Full Day price.
        $fullDay = $businessHours->fullDayWindow($owner, $validated['booking_date']);

        return response()->json([
            'rooms' => $options->values(),
            'full_day' => $fullDay ? ['start' => $fullDay['start'], 'end' => $fullDay['end']] : null,
        ]);
    }

    /**
     * Adds to whatever has already been collected on this booking — never a
     * replacement value — so a booking that was under-deposited at creation
     * (or a shared-room booking, which never takes a deposit up front) can
     * still end up correctly counted once the rest of the cash comes in.
     * Without this, RevenueAnalyticsService::bookingRevenue() would
     * permanently under-report any booking that wasn't paid in full at
     * booking time, since nothing else in the app ever touches amount_paid
     * again.
     */
    public function recordPayment(Request $request, $id): RedirectResponse
    {
        $ownerId = TenantContext::id();

        $booking = Booking::where('owner_id', $ownerId)->findOrFail($id);

        if (in_array($booking->status, ['cancelled', 'no_show'], true)) {
            return back()->with('error', __('app.booking.payment.cannot_record_cancelled'));
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|gt:0',
        ]);

        $amount = round((float) $validated['amount'], 2);
        $balanceDue = $booking->balanceDue();

        if ($amount > $balanceDue) {
            return back()->with('error', __('app.booking.payment.exceeds_balance', ['balance' => number_format($balanceDue, 2)]));
        }

        DB::transaction(function () use ($id, $ownerId, $amount) {
            $locked = Booking::where('id', $id)->where('owner_id', $ownerId)->lockForUpdate()->firstOrFail();
            $newPaid = round((float) $locked->amount_paid + $amount, 2);

            $locked->update([
                'amount_paid' => $newPaid,
                'payment_status' => Booking::derivePaymentStatus($newPaid, $locked->netRoomCharge()),
            ]);
        });

        $booking->refresh();
        $this->activityLogger->log('booking.payment_recorded', $booking, "Recorded a payment of {$amount} for booking #{$booking->id}");

        return back()->with('success', __('app.booking.payment.recorded'));
    }

    /**
     * Attach a coupon to a still-editable (pending/confirmed, exclusive-room)
     * booking. Only computes/stores the discount fields on the booking (and
     * its sale, if any) — usage is recorded later, only once the booking
     * actually completes (see updateStatus()).
     */
    public function applyCoupon(Request $request, $id, CouponService $coupons): RedirectResponse|JsonResponse
    {
        $booking = Booking::where('owner_id', TenantContext::id())->with('room', 'sale')->findOrFail($id);

        $validated = $request->validate(['code' => 'required|string|max:40']);

        try {
            $breakdown = $coupons->attachToBooking($booking, $validated['code']);
        } catch (CouponRejectedException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'coupon' => $breakdown->toArray()]);
        }

        return back()->with('success', __('app.coupons.checkout.applied'));
    }

    public function removeCoupon(Request $request, $id, CouponService $coupons): RedirectResponse|JsonResponse
    {
        $booking = Booking::where('owner_id', TenantContext::id())->with('sale')->findOrFail($id);

        $coupons->detachFromBooking($booking);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('app.coupons.checkout.removed'));
    }

    /**
     * Attach a product/service as a line item to this booking's sale.
     * Routed under feature:booking + feature:sales. Returns JSON when the
     * caller asks for it (the Active Sessions card's add-product modal, for
     * an in-progress exclusive-room booking) so one shared JS flow can drive
     * both this and SharedSessionController::addItem() without a full-page
     * reload; the standalone booking-detail page keeps its existing
     * synchronous back() redirect otherwise.
     */
    public function addItem(Request $request, $id, SalesService $sales, CouponService $coupons): RedirectResponse|JsonResponse
    {
        $ownerId = TenantContext::id();

        $booking = Booking::where('owner_id', $ownerId)->with('room')->findOrFail($id);

        // A shared-room reservation only gets a running tab once it's live
        // (checked_in) or settled (completed) — never while still
        // pending/confirmed. saleForBooking() creates its Sale as
        // 'completed' immediately, which would be wrong for a reservation
        // nobody has arrived for yet, and would risk a second, orphaned
        // Sale being created later when the session's own tab is
        // transferred at close (Sale has no unique constraint on booking_id).
        if ($booking->room->isShared() && ! in_array($booking->status, ['checked_in', 'completed'])) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Products can only be added once this reservation is checked in.'], 422);
            }

            return back()->with('error', 'Products can only be added once this reservation is checked in.');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:1000',
        ]);

        // Scope the product to this owner — never trust a product id from another tenant.
        $product = Product::where('id', $validated['product_id'])
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        // Stock is checked and taken server-side inside one transaction
        // (InventoryService); an inactive product or a shortfall adds nothing.
        $stockError = null;
        if (! $product->is_active) {
            $stockError = __('app.inventory.errors.inactive', ['name' => $product->name]);
        } else {
            try {
                DB::transaction(function () use ($sales, $booking, $product, $validated) {
                    $sale = $sales->saleForBooking($booking);
                    $sales->addItem($sale, $product, (int) $validated['quantity']);
                });
            } catch (InsufficientStockException $e) {
                $stockError = $e->getMessage();
            }
        }
        if ($stockError) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $stockError], 422)
                : back()->with('error', $stockError);
        }

        $this->activityLogger->log('booking.item_added', $booking, "Added {$validated['quantity']}x {$product->name} to booking #{$booking->id}");

        // A coupon attached earlier may no longer be valid now the cart
        // changed (e.g. it dropped below minimum spend) — re-evaluate rather
        // than leave a stale discount on the books.
        $warning = $coupons->syncBooking($booking->fresh(['sale.items']));

        if ($request->wantsJson()) {
            return response()->json(array_filter(['success' => true, 'coupon_warning' => $warning]));
        }

        $redirect = back()->with('success', __('app.sales.item_added'));

        return $warning ? $redirect->with('warning', $warning) : $redirect;
    }

    /** Remove a line item from this booking's sale. See addItem() for the JSON-response rationale. */
    public function removeItem(Request $request, $id, $itemId, SalesService $sales, CouponService $coupons): RedirectResponse|JsonResponse
    {
        $ownerId = TenantContext::id();

        $booking = Booking::where('owner_id', $ownerId)->with(['sale', 'room'])->findOrFail($id);

        if ($booking->room->isShared() && ! in_array($booking->status, ['checked_in', 'completed'])) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Products can only be managed once this reservation is checked in.'], 422);
            }

            return back()->with('error', 'Products can only be managed once this reservation is checked in.');
        }

        if ($booking->sale) {
            $item = SaleItem::where('id', $itemId)
                ->where('sale_id', $booking->sale->id)
                ->firstOrFail();

            $sales->removeItem($item);

            $this->activityLogger->log('booking.item_removed', $booking, "Removed a line item from booking #{$booking->id}");
        }

        $warning = $coupons->syncBooking($booking->fresh(['sale.items']));

        if ($request->wantsJson()) {
            return response()->json(array_filter(['success' => true, 'coupon_warning' => $warning]));
        }

        $redirect = back()->with('success', __('app.sales.item_removed'));

        return $warning ? $redirect->with('warning', $warning) : $redirect;
    }
}
