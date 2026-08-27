<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Owner;
use App\Models\Room;
use App\Models\SharedSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * "Right now" capacity across every room an owner has. This intentionally
 * duplicates AvailabilityService::usedCapacityNow()'s exact rules (today's
 * pending/confirmed bookings whose time window contains this instant, minus
 * any confirmed booking in a shared room that's already past its no-show
 * grace period, plus every open SharedSession's party_size) rather than
 * calling that method — AvailabilityService's method is built for a
 * single-room call site (the booking form, check-in) and issues two queries
 * per call; a dashboard needs every room's current state at once, and
 * calling it in a per-room loop would fan out to 2N queries. This batches
 * the same two underlying queries ONE time each for the whole owner and
 * reproduces the per-row exclusion rule in PHP over the already-fetched
 * rows, so the numbers match usedCapacityNow() exactly without the fan-out.
 */
class OccupancyAnalyticsService
{
    /**
     * @return array{
     *     capacity: int, occupied: int, available: int, percent: float,
     *     rooms: array<int, array{room_id:int, room_name:string, is_available:bool, capacity:int, occupied:int, available:int}>
     * }
     */
    public function currentOccupancy(Owner $owner): array
    {
        $now = Carbon::now();
        $rooms = Room::where('owner_id', $owner->id)->get(['id', 'name', 'type', 'capacity', 'is_available']);

        if ($rooms->isEmpty()) {
            return ['capacity' => 0, 'occupied' => 0, 'available' => 0, 'percent' => 0.0, 'rooms' => []];
        }

        $roomIds = $rooms->pluck('id');

        $overlappingNow = Booking::where('owner_id', $owner->id)
            ->whereIn('room_id', $roomIds)
            ->whereDate('booking_date', $now->format('Y-m-d'))
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_time', '<=', $now->format('H:i:s'))
            ->where('end_time', '>', $now->format('H:i:s'))
            ->get(['id', 'room_id', 'status', 'booking_date', 'start_time', 'party_size']);

        $bookingUsageByRoom = $overlappingNow
            ->groupBy('room_id')
            ->map(function (Collection $bookings, int $roomId) use ($rooms) {
                $room = $rooms->firstWhere('id', $roomId);
                $isShared = $room !== null && $room->type === 'shared';

                return (int) $bookings
                    ->reject(fn (Booking $b) => $isShared && $b->status === 'confirmed' && $b->isPastNoShowGrace())
                    ->sum('party_size');
            });

        $sessionUsageByRoom = SharedSession::where('owner_id', $owner->id)
            ->whereIn('room_id', $roomIds)
            ->where('status', 'open')
            ->selectRaw('room_id, SUM(party_size) as used')
            ->groupBy('room_id')
            ->pluck('used', 'room_id');

        $rows = $rooms->map(function (Room $room) use ($bookingUsageByRoom, $sessionUsageByRoom) {
            $capacity = $room->effectiveCapacity();
            $occupied = (int) ($bookingUsageByRoom[$room->id] ?? 0) + (int) ($sessionUsageByRoom[$room->id] ?? 0);

            return [
                'room_id' => $room->id,
                'room_name' => $room->name,
                'is_available' => (bool) $room->is_available,
                'capacity' => $capacity,
                'occupied' => $occupied,
                'available' => max(0, $capacity - $occupied),
            ];
        })->values();

        $totalCapacity = (int) $rows->sum('capacity');
        $totalOccupied = (int) $rows->sum('occupied');

        return [
            'capacity' => $totalCapacity,
            'occupied' => $totalOccupied,
            'available' => max(0, $totalCapacity - $totalOccupied),
            'percent' => $totalCapacity > 0 ? round(($totalOccupied / $totalCapacity) * 100, 1) : 0.0,
            'rooms' => $rows->all(),
        ];
    }

    /**
     * Rooms marked available (is_available=true) that also have free
     * capacity right now — an upgrade over the existing dashboard's plain
     * is_available count, per the approved design. Reuses currentOccupancy()
     * rather than re-querying, so this stays within the same fixed query
     * budget regardless of room count.
     */
    public function availableRoomsNow(Owner $owner): int
    {
        return collect($this->currentOccupancy($owner)['rooms'])
            ->filter(fn (array $r) => $r['is_available'] && $r['available'] > 0)
            ->count();
    }
}
