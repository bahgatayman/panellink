<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingCapacityExceededException;
use App\Exceptions\SharedSessionCapacityExceededException;
use App\Http\Controllers\Concerns\GeneratesTimeSlots;
use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Product;
use App\Models\Room;
use App\Models\SaleItem;
use App\Models\SharedSession;
use App\Models\Workspace;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\BusinessHoursService;
use App\Services\SalesService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    use GeneratesTimeSlots;

    public function index(Request $request): View
    {
        $ownerId = auth('owner')->id();
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
        $ownerId = auth('owner')->id();

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

    public function store(Request $request, AvailabilityService $availability, BusinessHoursService $businessHours): RedirectResponse
    {
        $owner = auth('owner')->user();
        $ownerId = $owner->id;

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'hotspot_user_id' => 'required|exists:hotspot_users,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'party_size' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        if (! $businessHours->isWithinWorkingHours($owner, $validated['booking_date'], $validated['start_time'], $validated['end_time'])) {
            return back()->withInput()->with('error', __('app.booking.outside_working_hours'));
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

        $bookingService = new BookingService;
        $calc = $bookingService->calculateBooking(
            $validated['start_time'],
            $validated['end_time'],
            $room->price_per_hour,
        );

        try {
            $booking = DB::transaction(function () use ($validated, $ownerId, $room, $hotspotUser, $calc, $availability, $partySize) {
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
                    'hotspot_user_id' => $hotspotUser->id,
                    'party_size' => $partySize,
                    'booking_date' => $validated['booking_date'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'price_per_hour' => $lockedRoom->price_per_hour,
                    'total_hours' => $calc['total_hours'],
                    'total_price' => $calc['total_price'],
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
            return back()->withInput()->with('error',
                'This room is already booked for the selected time slot. Please choose a different time.');
        }

        return redirect("/bookings/{$booking->id}")->with('success', 'Booking confirmed successfully.');
    }

    public function show($id): View
    {
        $owner = auth('owner')->user();

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
        $ownerId = auth('owner')->id();

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

    public function update(Request $request, $id, AvailabilityService $availability, BusinessHoursService $businessHours): RedirectResponse
    {
        $owner = auth('owner')->user();
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
            'end_time' => 'required|date_format:H:i|after:start_time',
            'party_size' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        if (! $businessHours->isWithinWorkingHours($owner, $validated['booking_date'], $validated['start_time'], $validated['end_time'])) {
            return back()->withInput()->with('error', __('app.booking.outside_working_hours'));
        }

        $room = Room::where('id', $validated['room_id'])
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        $partySize = (int) ($validated['party_size'] ?? 1);

        $bookingService = new BookingService;
        $calc = $bookingService->calculateBooking(
            $validated['start_time'],
            $validated['end_time'],
            $room->price_per_hour,
        );

        try {
            $updated = DB::transaction(function () use ($validated, $room, $booking, $calc, $availability, $id, $partySize) {
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
                    'hotspot_user_id' => $validated['hotspot_user_id'],
                    'party_size' => $partySize,
                    'booking_date' => $validated['booking_date'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'price_per_hour' => $lockedRoom->price_per_hour,
                    'total_hours' => $calc['total_hours'],
                    'total_price' => $calc['total_price'],
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

        return redirect("/bookings/{$id}")->with('success', 'Booking updated successfully.');
    }

    public function updateStatus(Request $request, $id): RedirectResponse
    {
        $booking = Booking::where('owner_id', auth('owner')->id())->with('room')->findOrFail($id);

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

        $booking->update(['status' => $validated['status']]);

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
    public function checkIn(Request $request, $id, AvailabilityService $availability, BusinessHoursService $businessHours): RedirectResponse
    {
        $owner = auth('owner')->user();
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
            $session = DB::transaction(function () use ($booking, $ownerId, $actualPartySize, $availability) {
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

        return redirect()->route('shared-sessions.index')
            ->with('success', 'Checked in. The session is now open.');
    }

    public function destroy($id): RedirectResponse
    {
        $booking = Booking::where('owner_id', auth('owner')->id())->findOrFail($id);

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
        $ownerId = auth('owner')->id();

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
        $ownerId = auth('owner')->id();

        $rooms = Room::where('owner_id', $ownerId)
            ->where('is_available', true)
            ->with('workspace')
            ->orderBy('name')
            ->get();

        $timeSlots = $this->generateTimeSlots();

        return view('bookings.availability', compact('rooms', 'timeSlots'));
    }

    public function checkAvailability(Request $request, AvailabilityService $availability, BusinessHoursService $businessHours): JsonResponse
    {
        $owner = auth('owner')->user();

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'booking_id' => 'nullable|exists:bookings,id',
            'party_size' => 'nullable|integer|min:1',
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

        $isAvailable = $partySize <= $remaining && $withinHours;

        $calc = null;
        if ($isAvailable) {
            $service = new BookingService;
            $calc = $service->calculateBooking(
                $validated['start_time'],
                $validated['end_time'],
                $room->price_per_hour,
            );
        }

        return response()->json([
            'available' => $isAvailable,
            'remaining' => $remaining,
            'total_hours' => $calc['total_hours'] ?? null,
            'total_price' => $calc['total_price'] ?? null,
            'price_per_hour' => $room->price_per_hour,
        ]);
    }

    /**
     * Attach a product/service as a line item to this booking's sale.
     * Routed under feature:booking + feature:sales.
     */
    public function addItem(Request $request, $id, SalesService $sales): RedirectResponse
    {
        $ownerId = auth('owner')->id();

        $booking = Booking::where('owner_id', $ownerId)->with('room')->findOrFail($id);

        // A shared-room reservation only gets a running tab once it's live
        // (checked_in) or settled (completed) — never while still
        // pending/confirmed. saleForBooking() creates its Sale as
        // 'completed' immediately, which would be wrong for a reservation
        // nobody has arrived for yet, and would risk a second, orphaned
        // Sale being created later when the session's own tab is
        // transferred at close (Sale has no unique constraint on booking_id).
        if ($booking->room->isShared() && ! in_array($booking->status, ['checked_in', 'completed'])) {
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

        $sale = $sales->saleForBooking($booking);
        $sales->addItem($sale, $product, (int) $validated['quantity']);

        return back()->with('success', __('app.sales.item_added'));
    }

    /** Remove a line item from this booking's sale. */
    public function removeItem($id, $itemId, SalesService $sales): RedirectResponse
    {
        $ownerId = auth('owner')->id();

        $booking = Booking::where('owner_id', $ownerId)->with(['sale', 'room'])->findOrFail($id);

        if ($booking->room->isShared() && ! in_array($booking->status, ['checked_in', 'completed'])) {
            return back()->with('error', 'Products can only be managed once this reservation is checked in.');
        }

        if ($booking->sale) {
            $item = SaleItem::where('id', $itemId)
                ->where('sale_id', $booking->sale->id)
                ->firstOrFail();

            $sales->removeItem($item);
        }

        return back()->with('success', __('app.sales.item_removed'));
    }
}
