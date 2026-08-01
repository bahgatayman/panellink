<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Product;
use App\Models\Room;
use App\Models\SaleItem;
use App\Services\BookingService;
use App\Services\SalesService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $ownerId = auth('owner')->id();
        $status  = $request->get('status');
        $date    = $request->get('date');
        $roomId  = $request->get('room_id');

        $bookings = Booking::where('owner_id', $ownerId)
            ->with(['room.workspace', 'hotspotUser'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($date,   fn($q) => $q->where('booking_date', $date))
            ->when($roomId, fn($q) => $q->where('room_id', $roomId))
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

        return view('bookings.create', compact('rooms', 'users', 'timeSlots', 'selectedUserId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $ownerId = auth('owner')->id();

        $validated = $request->validate([
            'room_id'         => 'required|exists:rooms,id',
            'hotspot_user_id' => 'required|exists:hotspot_users,id',
            'booking_date'    => 'required|date|after_or_equal:today',
            'start_time'      => 'required|date_format:H:i',
            'end_time'        => 'required|date_format:H:i|after:start_time',
            'notes'           => 'nullable|string|max:500',
        ]);

        $room = Room::where('id', $validated['room_id'])
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        if ($room->isShared()) {
            return redirect()->route('shared-sessions.room', $room->id)
                ->with('info', 'This is a shared room. Use the Session Panel to open sessions for users.');
        }

        $hotspotUser = HotspotUser::where('id', $validated['hotspot_user_id'])
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        if ($room->hasConflict($validated['booking_date'], $validated['start_time'], $validated['end_time'])) {
            return back()->withInput()->with('error',
                'This room is already booked for the selected time slot. Please choose a different time.');
        }

        $bookingService = new BookingService();
        $calc = $bookingService->calculateBooking(
            $validated['start_time'],
            $validated['end_time'],
            $room->price_per_hour,
        );

        $booking = Booking::create([
            'owner_id'        => $ownerId,
            'room_id'         => $room->id,
            'hotspot_user_id' => $hotspotUser->id,
            'booking_date'    => $validated['booking_date'],
            'start_time'      => $validated['start_time'],
            'end_time'        => $validated['end_time'],
            'price_per_hour'  => $room->price_per_hour,
            'total_hours'     => $calc['total_hours'],
            'total_price'     => $calc['total_price'],
            'status'          => 'confirmed',
            'notes'           => $validated['notes'],
        ]);

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

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
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

    public function update(Request $request, $id): RedirectResponse
    {
        $ownerId = auth('owner')->id();

        $booking = Booking::where('owner_id', $ownerId)->findOrFail($id);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Only pending or confirmed bookings can be edited.');
        }

        $validated = $request->validate([
            'room_id'         => 'required|exists:rooms,id',
            'hotspot_user_id' => 'required|exists:hotspot_users,id',
            'booking_date'    => 'required|date',
            'start_time'      => 'required|date_format:H:i',
            'end_time'        => 'required|date_format:H:i|after:start_time',
            'notes'           => 'nullable|string|max:500',
        ]);

        $room = Room::where('id', $validated['room_id'])
            ->where('owner_id', $ownerId)
            ->firstOrFail();

        if ($room->isShared()) {
            return redirect()->route('shared-sessions.room', $room->id)
                ->with('info', 'This is a shared room. Use the Session Panel to open sessions for users.');
        }

        if ($room->hasConflict($validated['booking_date'], $validated['start_time'], $validated['end_time'], $id)) {
            return back()->withInput()->with('error',
                'This room is already booked for the selected time slot. Please choose a different time.');
        }

        $bookingService = new BookingService();
        $calc = $bookingService->calculateBooking(
            $validated['start_time'],
            $validated['end_time'],
            $room->price_per_hour,
        );

        $booking->update([
            'room_id'         => $room->id,
            'hotspot_user_id' => $validated['hotspot_user_id'],
            'booking_date'    => $validated['booking_date'],
            'start_time'      => $validated['start_time'],
            'end_time'        => $validated['end_time'],
            'price_per_hour'  => $room->price_per_hour,
            'total_hours'     => $calc['total_hours'],
            'total_price'     => $calc['total_price'],
            'notes'           => $validated['notes'],
        ]);

        return redirect("/bookings/{$id}")->with('success', 'Booking updated successfully.');
    }

    public function updateStatus(Request $request, $id): RedirectResponse
    {
        $booking = Booking::where('owner_id', auth('owner')->id())->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $validTransitions = [
            'pending'   => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        if (!in_array($validated['status'], $validTransitions[$booking->status] ?? [])) {
            return back()->with('error', 'Invalid status transition.');
        }

        $booking->update(['status' => $validated['status']]);

        return back()->with('success', 'Booking status updated to ' . $booking->statusLabel() . '.');
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

    public function calendar(Request $request): View
    {
        $ownerId = auth('owner')->id();
        $date    = $request->get('date', now()->format('Y-m-d'));
        $carbon  = Carbon::parse($date);

        $bookings = Booking::where('owner_id', $ownerId)
            ->with(['room.workspace', 'hotspotUser'])
            ->whereMonth('booking_date', $carbon->month)
            ->whereYear('booking_date', $carbon->year)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->groupBy(fn($b) => $b->booking_date->format('Y-m-d'));

        $rooms = Room::where('owner_id', $ownerId)
            ->where('is_available', true)
            ->with('workspace')
            ->get();

        return view('bookings.calendar', compact('bookings', 'carbon', 'date', 'rooms'));
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id'      => 'required|exists:rooms,id',
            'booking_date' => 'required|date',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'booking_id'   => 'nullable|exists:bookings,id',
        ]);

        $room = Room::where('id', $validated['room_id'])
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $hasConflict = $room->hasConflict(
            $validated['booking_date'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['booking_id'],
        );

        $calc = null;
        if (!$hasConflict) {
            $service = new BookingService();
            $calc = $service->calculateBooking(
                $validated['start_time'],
                $validated['end_time'],
                $room->price_per_hour,
            );
        }

        return response()->json([
            'available'      => !$hasConflict,
            'total_hours'    => $calc['total_hours'] ?? null,
            'total_price'    => $calc['total_price'] ?? null,
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

        $booking = Booking::where('owner_id', $ownerId)->findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1|max:1000',
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

        $booking = Booking::where('owner_id', $ownerId)->with('sale')->findOrFail($id);

        if ($booking->sale) {
            $item = SaleItem::where('id', $itemId)
                ->where('sale_id', $booking->sale->id)
                ->firstOrFail();

            $sales->removeItem($item);
        }

        return back()->with('success', __('app.sales.item_removed'));
    }

    private function generateTimeSlots(): array
    {
        $slots = [];
        $start = Carbon::createFromTime(6, 0);
        $end   = Carbon::createFromTime(23, 30);

        while ($start->lte($end)) {
            $slots[$start->format('H:i')] = $start->format('h:i A');
            $start->addMinutes(30);
        }

        return $slots;
    }
}
