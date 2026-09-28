<?php

namespace App\Http\Controllers;

use App\Exceptions\SharedSessionCapacityExceededException;
use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Product;
use App\Models\Room;
use App\Models\SaleItem;
use App\Models\SharedSession;
use App\Services\ActivityLogger;
use App\Services\AvailabilityService;
use App\Services\BusinessHoursService;
use App\Services\RoomPricingService;
use App\Services\SalesService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SharedSessionController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger,
        private RoomPricingService $pricing,
    ) {}

    public function create(): View
    {
        $sharedRooms = Room::where('owner_id', TenantContext::id())
            ->where('type', 'shared')
            ->withSum(['sharedSessions as occupied_seats' => function ($q) {
                $q->where('status', 'open');
            }], 'party_size')
            ->with('workspace')
            ->get();

        return view('active-sessions.create', compact('sharedRooms'));
    }

    public function store(Request $request, AvailabilityService $availability, BusinessHoursService $businessHours): RedirectResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'hotspot_user_id' => 'required|exists:hotspot_users,id',
            'session_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'party_size' => 'nullable|integer|min:1',
        ]);

        $owner = TenantContext::user();
        $ownerId = $owner->id;
        $partySize = (int) ($request->input('party_size') ?: 1);

        if (! $businessHours->isOpenAt($owner, Carbon::parse($request->session_date.' '.$request->start_time))) {
            return back()->withInput()->with('error', __('app.session.outside_working_hours'));
        }

        $user = HotspotUser::where('id', $request->hotspot_user_id)
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        // The capacity check and the insert must happen atomically: two staff
        // opening large parties into the room's last few free seats at the same
        // moment must not both pass the check and jointly overbook it.
        // lockForUpdate() genuinely serializes this on MySQL (production); it's a
        // no-op on SQLite (dev/test) — the post-write exceedsCapacity()-style
        // re-check below is the second, engine-independent layer this doesn't
        // rely on alone, mirroring BookingController::store()'s identical
        // defense-in-depth pattern.
        try {
            [$roomName, $error] = DB::transaction(function () use ($request, $ownerId, $user, $partySize, $availability) {
                $room = Room::where('id', $request->room_id)
                    ->where('owner_id', $ownerId)
                    ->where('type', 'shared')
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($partySize > $room->effectiveCapacity()) {
                    return [null, "This room only seats {$room->capacity} people."];
                }

                // The unified "right now" formula — capacity already claimed
                // by overlapping advance Bookings AND other open sessions,
                // not just open sessions in isolation. Room::availableSharedSlots()
                // is blind to the bookings table entirely and is no longer
                // safe to gate a write with now that shared rooms can be
                // advance-booked (Phase 5).
                $available = $availability->availableNow($room);
                if ($partySize > $available) {
                    return [null, "Only {$available} of {$room->capacity} seats available right now."];
                }

                $existing = SharedSession::where('room_id', $room->id)
                    ->where('hotspot_user_id', $user->id)
                    ->where('status', 'open')
                    ->exists();

                if ($existing) {
                    return [null, "{$user->name} already has an open session in this room."];
                }

                $openedAt = Carbon::parse($request->session_date.' '.$request->start_time);

                // Snapshotted from the room now, at open time — never read live
                // from the room again for this session. Without this, an Owner
                // changing the room's billing_unit or price_per_hour while this
                // session is already open would silently change its final bill.
                SharedSession::create([
                    'owner_id' => $ownerId,
                    'room_id' => $room->id,
                    'hotspot_user_id' => $user->id,
                    'party_size' => $partySize,
                    'session_date' => $request->session_date,
                    'start_time' => $request->start_time,
                    'opened_at' => $openedAt,
                    'status' => 'open',
                    'billing_unit' => $room->billing_unit,
                    'billed_price_per_hour' => $room->price_per_hour,
                    'pricing_snapshot' => $this->pricing->snapshotFor($room),
                ]);

                if ($availability->usedCapacityNow($room) > $room->effectiveCapacity()) {
                    throw new SharedSessionCapacityExceededException;
                }

                return [$room->name, null];
            });
        } catch (SharedSessionCapacityExceededException) {
            return back()->withInput()->with('error', 'This room is already full. Please try again.');
        }

        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        $session = SharedSession::where('owner_id', $ownerId)->where('room_id', $request->room_id)
            ->where('hotspot_user_id', $user->id)->where('status', 'open')->latest('id')->first();
        if ($session) {
            $this->activityLogger->log('shared_session.opened', $session, "Opened session for {$user->name} in {$roomName}");
        }

        return redirect()->route('active-sessions.index')
            ->with('success', "Session opened for {$user->name} in {$roomName}.");
    }

    public function closePreview(int $sessionId): JsonResponse
    {
        $session = SharedSession::where('id', $sessionId)
            ->where('owner_id', TenantContext::id())
            ->where('status', 'open')
            ->with(['room', 'hotspotUser', 'sale.items'])
            ->firstOrFail();

        $closedAt = now();
        $quote = $this->pricing->quoteSession($session, $closedAt);

        $duration = $this->formatMinutes($quote->totalMinutes);

        // Only shown when the billed time actually differs from the time
        // used (block billing rounded up, or a package longer than the time
        // used) — a session billed exactly what it used has nothing to clarify.
        $billedDuration = abs($quote->totalMinutes - $quote->billedMinutes) > 0.01
            ? $this->formatMinutes($quote->billedMinutes)
            : null;

        $itemsTotal = (float) ($session->sale?->total ?? 0);
        $grandTotal = round($quote->totalPrice + $itemsTotal, 2);

        return response()->json([
            'session_id' => $session->id,
            'user_name' => $session->hotspotUser->name,
            'user_phone' => $session->hotspotUser->phone,
            'room_name' => $session->room->name,
            'party_size' => $session->party_size,
            'start_time' => $session->opened_at->format('h:i A'),
            'end_time' => $closedAt->format('h:i A'),
            'closed_at_datetime' => $closedAt->toDateTimeString(),
            'duration' => $duration,
            'billed_duration' => $billedDuration,
            'total_minutes' => $quote->totalMinutes,
            'price_per_hour' => number_format($quote->ratePerHour, 2),
            'pricing_note' => $quote->note,
            'total_price' => number_format($quote->totalPrice, 2),
            'total_price_raw' => $quote->totalPrice,
            'items' => $this->itemsPayload($session),
            'items_total' => number_format($itemsTotal, 2),
            'grand_total' => number_format($grandTotal, 2),
        ]);
    }

    /** Add a product to the session's running tab. Routed under feature:booking + feature:sales. */
    public function addItem(Request $request, int $sessionId, SalesService $sales): JsonResponse
    {
        $ownerId = TenantContext::id();

        $session = SharedSession::where('id', $sessionId)
            ->where('owner_id', $ownerId)
            ->where('status', 'open')
            ->firstOrFail();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:1000',
        ]);

        // Scope the product to this owner — never trust a product id from another tenant.
        $product = Product::where('id', $validated['product_id'])
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        $sale = $sales->saleForSharedSession($session);
        $sales->addItem($sale, $product, (int) $validated['quantity']);

        $this->activityLogger->log('shared_session.item_added', $session, "Added {$validated['quantity']}x {$product->name} to session #{$session->id}");

        return response()->json(['success' => true]);
    }

    /** Remove a line item from the session's running tab. */
    public function removeItem(int $sessionId, int $itemId, SalesService $sales): JsonResponse
    {
        $ownerId = TenantContext::id();

        $session = SharedSession::where('id', $sessionId)
            ->where('owner_id', $ownerId)
            ->where('status', 'open')
            ->with('sale')
            ->firstOrFail();

        if ($session->sale) {
            $item = SaleItem::where('id', $itemId)
                ->where('sale_id', $session->sale->id)
                ->firstOrFail();

            $sales->removeItem($item);

            $this->activityLogger->log('shared_session.item_removed', $session, "Removed a line item from session #{$session->id}");
        }

        return response()->json(['success' => true]);
    }

    /** 'Xh Ym' for a fractional-minutes value, used by both the actual and billed duration strings. */
    private function formatMinutes(float $minutes): string
    {
        $h = intdiv((int) $minutes, 60);
        $m = (int) $minutes % 60;

        return ($h > 0 ? $h.'h ' : '').$m.'m';
    }

    /** Serialise the tab's line items for the close modal. */
    private function itemsPayload(SharedSession $session): array
    {
        if (! $session->sale) {
            return [];
        }

        return $session->sale->items->map(fn (SaleItem $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'quantity' => $item->quantity,
            'unit_price' => number_format($item->unit_price, 2),
            'line_total' => number_format($item->line_total, 2),
        ])->all();
    }

    /**
     * Close an open session. Money is always computed here, server-side, from
     * opened_at → now(): the client is never trusted for total_minutes/total_price
     * (it previously posted its own preview-time numbers straight into the
     * booking — a browser should never be the source of truth for a charge).
     *
     * The status flip is a single atomic UPDATE guarded by WHERE status='open',
     * checked for affected rows, before anything else runs. That closes the
     * double-close race (two concurrent clicks/requests): only one request can
     * ever see affected=1 and proceed; the other sees 0 and is rejected. This
     * works identically on SQLite (dev/test) and MySQL (production) — a single
     * UPDATE statement is atomic on both — unlike lockForUpdate(), which SQLite
     * does not honor.
     */
    public function close(int $sessionId, SalesService $sales): JsonResponse
    {
        $ownerId = TenantContext::id();
        $closedAt = now();

        return DB::transaction(function () use ($sessionId, $ownerId, $closedAt, $sales) {
            $claimed = SharedSession::where('id', $sessionId)
                ->where('owner_id', $ownerId)
                ->where('status', 'open')
                ->update(['status' => 'closed', 'closed_at' => $closedAt]);

            if ($claimed === 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('app.session.already_closed'),
                ], 409);
            }

            $session = SharedSession::where('id', $sessionId)
                ->where('owner_id', $ownerId)
                ->with(['room', 'hotspotUser', 'sale'])
                ->firstOrFail();

            // Same service (and the same snapshotted pricing) as
            // closePreview() — the two must never disagree on what a session
            // is about to cost.
            $quote = $this->pricing->quoteSession($session, $closedAt);
            $totalHours = $quote->billedHours();

            $booking = Booking::create([
                'owner_id' => $ownerId,
                'room_id' => $session->room_id,
                'hotspot_user_id' => $session->hotspot_user_id,
                'party_size' => $session->party_size,
                'booking_date' => $session->session_date,
                'start_time' => $session->start_time,
                'end_time' => $closedAt->format('H:i'),
                'price_per_hour' => $quote->ratePerHour,
                'total_hours' => $totalHours,
                'total_price' => $quote->totalPrice,
                'pricing_note' => $quote->note,
                // A closed shared/walk-in session is cash collected at the
                // register right now — always fully paid. Without this, the
                // Financials revenue switch to amount_paid would silently
                // zero out every walk-in session's revenue.
                'amount_paid' => $quote->totalPrice,
                'payment_status' => Booking::PAYMENT_PAID,
                'status' => 'completed',
                'notes' => 'Auto-created from shared session.',
            ]);

            $session->update([
                'total_minutes' => $quote->totalMinutes,
                'total_price' => $quote->totalPrice,
                'booking_id' => $booking->id,
            ]);

            // Move the running tab (if any) onto the booking, keeping its line items.
            if ($session->sale) {
                $sales->transferToBooking($session->sale, $booking);
            }

            $grandTotal = $quote->totalPrice + (float) ($session->sale?->total ?? 0);

            $this->activityLogger->log('shared_session.closed', $session, "Closed session #{$session->id}, total ج.م ".number_format($grandTotal, 2));

            return response()->json([
                'success' => true,
                'message' => 'Session closed. Total: ج.م '.number_format($grandTotal, 2),
                'booking_id' => $booking->id,
            ]);
        });
    }
}
