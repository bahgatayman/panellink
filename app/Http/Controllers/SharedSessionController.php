<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Product;
use App\Models\Room;
use App\Models\SaleItem;
use App\Models\SharedSession;
use App\Services\SalesService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SharedSessionController extends Controller
{
    public function index(): View
    {
        $owner = auth('owner')->user();

        $openSessions = SharedSession::where('owner_id', $owner->id)
            ->where('status', 'open')
            ->with(['room.workspace', 'hotspotUser'])
            ->orderBy('opened_at', 'asc')
            ->get();

        // occupied_seats sums party_size, not row count — a party of 5 must
        // register as 5 seats used, not 1 (see Room::availableSharedSlots()).
        $sharedRooms = Room::where('owner_id', $owner->id)
            ->where('type', 'shared')
            ->withSum(['sharedSessions as occupied_seats' => function ($q) {
                $q->where('status', 'open');
            }], 'party_size')
            ->with('workspace')
            ->get();

        // Catalog for the running-tab picker (only when the sales feature is on).
        $products = $owner->hasFeature('sales')
            ? Product::where('owner_id', $owner->id)->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('shared-sessions.index', compact('openSessions', 'sharedRooms', 'products'));
    }

    public function create(): View
    {
        $sharedRooms = Room::where('owner_id', auth('owner')->id())
            ->where('type', 'shared')
            ->withSum(['sharedSessions as occupied_seats' => function ($q) {
                $q->where('status', 'open');
            }], 'party_size')
            ->with('workspace')
            ->get();

        return view('shared-sessions.create', compact('sharedRooms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'hotspot_user_id' => 'required|exists:hotspot_users,id',
            'session_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'party_size' => 'nullable|integer|min:1',
        ]);

        $ownerId = auth('owner')->id();
        $partySize = (int) ($request->input('party_size') ?: 1);

        $user = HotspotUser::where('id', $request->hotspot_user_id)
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        // The capacity check and the insert must happen atomically: two staff
        // opening large parties into the room's last few free seats at the same
        // moment must not both pass the check and jointly overbook it.
        // lockForUpdate() genuinely serializes this on MySQL (production); it's a
        // no-op on SQLite (dev/test), same documented limitation as close()'s
        // double-close guard.
        [$roomName, $error] = DB::transaction(function () use ($request, $ownerId, $user, $partySize) {
            $room = Room::where('id', $request->room_id)
                ->where('owner_id', $ownerId)
                ->where('type', 'shared')
                ->lockForUpdate()
                ->firstOrFail();

            if ($partySize > $room->capacity) {
                return [null, "This room only seats {$room->capacity} people."];
            }

            $available = $room->availableSharedSlots();
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

            SharedSession::create([
                'owner_id' => $ownerId,
                'room_id' => $room->id,
                'hotspot_user_id' => $user->id,
                'party_size' => $partySize,
                'session_date' => $request->session_date,
                'start_time' => $request->start_time,
                'opened_at' => $openedAt,
                'status' => 'open',
            ]);

            return [$room->name, null];
        });

        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        return redirect()->route('shared-sessions.index')
            ->with('success', "Session opened for {$user->name} in {$roomName}.");
    }

    public function closePreview(int $sessionId): JsonResponse
    {
        $session = SharedSession::where('id', $sessionId)
            ->where('owner_id', auth('owner')->id())
            ->where('status', 'open')
            ->with(['room', 'hotspotUser', 'sale.items'])
            ->firstOrFail();

        $closedAt = now();
        $totalMinutes = round($session->opened_at->diffInSeconds($closedAt) / 60, 2);
        $totalHours = round($totalMinutes / 60, 4);
        $totalPrice = round($totalHours * $session->room->price_per_hour, 2);

        $h = intdiv((int) $totalMinutes, 60);
        $m = (int) $totalMinutes % 60;
        $duration = ($h > 0 ? $h.'h ' : '').$m.'m';

        $itemsTotal = (float) ($session->sale?->total ?? 0);
        $grandTotal = round($totalPrice + $itemsTotal, 2);

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
            'total_minutes' => $totalMinutes,
            'price_per_hour' => number_format($session->room->price_per_hour, 2),
            'total_price' => number_format($totalPrice, 2),
            'total_price_raw' => $totalPrice,
            'items' => $this->itemsPayload($session),
            'items_total' => number_format($itemsTotal, 2),
            'grand_total' => number_format($grandTotal, 2),
        ]);
    }

    /** Add a product to the session's running tab. Routed under feature:booking + feature:sales. */
    public function addItem(Request $request, int $sessionId, SalesService $sales): JsonResponse
    {
        $ownerId = auth('owner')->id();

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

        return response()->json(['success' => true]);
    }

    /** Remove a line item from the session's running tab. */
    public function removeItem(int $sessionId, int $itemId, SalesService $sales): JsonResponse
    {
        $ownerId = auth('owner')->id();

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
        }

        return response()->json(['success' => true]);
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
        $ownerId = auth('owner')->id();
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

            // Same precision as closePreview() — the two must never disagree on
            // what a session is about to cost.
            $totalMinutes = round($session->opened_at->diffInSeconds($closedAt) / 60, 2);
            $totalHours = round($totalMinutes / 60, 4);
            $totalPrice = round($totalHours * $session->room->price_per_hour, 2);

            $booking = Booking::create([
                'owner_id' => $ownerId,
                'room_id' => $session->room_id,
                'hotspot_user_id' => $session->hotspot_user_id,
                'party_size' => $session->party_size,
                'booking_date' => $session->session_date,
                'start_time' => $session->start_time,
                'end_time' => $closedAt->format('H:i'),
                'price_per_hour' => $session->room->price_per_hour,
                'total_hours' => $totalHours,
                'total_price' => $totalPrice,
                'status' => 'completed',
                'notes' => 'Auto-created from shared session.',
            ]);

            $session->update([
                'total_minutes' => $totalMinutes,
                'total_price' => $totalPrice,
                'booking_id' => $booking->id,
            ]);

            // Move the running tab (if any) onto the booking, keeping its line items.
            if ($session->sale) {
                $sales->transferToBooking($session->sale, $booking);
            }

            $grandTotal = $totalPrice + (float) ($session->sale?->total ?? 0);

            return response()->json([
                'success' => true,
                'message' => 'Session closed. Total: ج.م '.number_format($grandTotal, 2),
                'booking_id' => $booking->id,
            ]);
        });
    }
}
