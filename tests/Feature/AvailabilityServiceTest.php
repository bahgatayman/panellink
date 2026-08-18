<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\Workspace;
use App\Services\AvailabilityService;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $availability;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
        $this->availability = app(AvailabilityService::class);
    }

    private function owner(): Owner
    {
        $plan = Plan::create([
            'name' => 'Test', 'slug' => 'test-'.uniqid(), 'max_members' => 100,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1,
            'features' => ['workspace', 'booking'],
            'max_workspaces' => 0, 'max_rooms' => 0, 'max_products' => 0,
        ]);

        $owner = Owner::create([
            'name' => 'Owner', 'email' => 'o'.uniqid().'@t.local', 'password' => 'secret123',
            'business_name' => 'Space', 'plan_id' => $plan->id, 'is_active' => true,
            'subscription_starts_at' => now(), 'subscription_expires_at' => now()->addMonth(),
        ]);

        foreach ($plan->features as $key) {
            $owner->enableFeature($key);
        }

        return $owner;
    }

    private function room(Owner $owner, string $type, int $capacity): Room
    {
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);

        return Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Room A',
            'type' => $type, 'capacity' => $capacity, 'price_per_hour' => 50,
        ]);
    }

    private function member(Owner $owner, string $name = 'Member'): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => $name, 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    private function booking(Owner $owner, Room $room, string $date, string $start, string $end, int $partySize = 1, string $status = 'confirmed'): Booking
    {
        return Booking::create([
            'owner_id' => $owner->id, 'room_id' => $room->id,
            'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => $partySize,
            'booking_date' => $date, 'start_time' => $start, 'end_time' => $end,
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 100,
            'status' => $status,
        ]);
    }

    // --- Exclusive rooms collapse to the capacity=1 special case ---

    public function test_exclusive_room_any_overlap_makes_it_unavailable(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'meeting', capacity: 6); // capacity is decorative for meeting rooms
        $this->booking($owner, $room, '2026-09-01', '09:00', '11:00');

        $this->assertSame(0, $this->availability->availabilityForRange($room, '2026-09-01', '10:00', '10:30'));
        $this->assertFalse($this->availability->availabilityForRange($room, '2026-09-01', '10:00', '10:30') > 0);
    }

    public function test_exclusive_room_adjacent_touching_bookings_are_not_conflicts(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'meeting', capacity: 1);
        $this->booking($owner, $room, '2026-09-01', '09:00', '11:00');

        // 11:00-13:00 starts exactly when the first ends — not an overlap.
        $this->assertSame(1, $this->availability->availabilityForRange($room, '2026-09-01', '11:00', '13:00'));
    }

    public function test_cancelled_bookings_do_not_consume_capacity(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'meeting', capacity: 1);
        $this->booking($owner, $room, '2026-09-01', '09:00', '11:00', status: 'cancelled');

        $this->assertSame(1, $this->availability->availabilityForRange($room, '2026-09-01', '09:00', '11:00'));
    }

    // --- effectiveCapacity() fat-finger backstop ---

    public function test_effective_capacity_ignores_stored_value_for_non_shared_types(): void
    {
        $owner = $this->owner();

        foreach (['meeting', 'training', 'office'] as $type) {
            $room = $this->room($owner, $type, capacity: 25); // fat-fingered capacity
            $this->assertSame(1, $room->effectiveCapacity(), "Expected {$type} rooms to always be exclusive.");
        }

        $shared = $this->room($owner, 'shared', capacity: 25);
        $this->assertSame(25, $shared->effectiveCapacity());
    }

    // --- Shared rooms: capacity-sum, matching the brief's own example ---

    public function test_shared_room_admits_multiple_overlapping_parties_within_capacity(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', capacity: 10);
        $this->booking($owner, $room, '2026-09-01', '14:00', '16:00', partySize: 3); // Ahmed, 3 people

        // "3/10 occupied, 7 available" — another party of 4 still fits.
        $this->assertSame(7, $this->availability->availabilityForRange($room, '2026-09-01', '14:00', '16:00'));
        $this->assertGreaterThanOrEqual(4, $this->availability->availabilityForRange($room, '2026-09-01', '14:00', '16:00'));
    }

    public function test_shared_room_rejects_a_party_that_no_longer_fits(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', capacity: 10);
        $this->booking($owner, $room, '2026-09-01', '14:00', '16:00', partySize: 3);
        $this->booking($owner, $room, '2026-09-01', '14:30', '15:30', partySize: 4);

        // 3 + 4 = 7 used, 3 free — a party of 5 does not fit.
        $remaining = $this->availability->availabilityForRange($room, '2026-09-01', '14:00', '16:00');
        $this->assertSame(3, $remaining);
        $this->assertLessThan(5, $remaining);
    }

    // --- excludeBookingId: editing a booking must not count itself ---

    public function test_editing_a_booking_excludes_its_own_row_from_the_sum(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'meeting', capacity: 1);
        $booking = $this->booking($owner, $room, '2026-09-01', '09:00', '11:00');

        // Without excluding itself, a booking would appear to conflict with its own slot.
        $this->assertSame(0, $this->availability->availabilityForRange($room, '2026-09-01', '09:00', '11:00'));
        $this->assertSame(1, $this->availability->availabilityForRange($room, '2026-09-01', '09:00', '11:00', excludeBookingId: $booking->id));
    }

    // --- freeBusyForDay(): the literal brief example ---

    public function test_free_busy_for_day_matches_the_brief_example(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'meeting', capacity: 1);
        $this->booking($owner, $room, '2026-09-01', '09:00', '11:00');
        $this->booking($owner, $room, '2026-09-01', '13:00', '15:00');

        $segments = $this->availability->freeBusyForDay($room, '2026-09-01');

        $summary = array_map(fn ($s) => [$s['start'], $s['end'], $s['used'] > 0 ? 'busy' : 'free'], $segments);

        $this->assertContains(['09:00', '11:00', 'busy'], $summary);
        $this->assertContains(['11:00', '13:00', 'free'], $summary);
        $this->assertContains(['13:00', '15:00', 'busy'], $summary);
    }

    public function test_free_busy_for_day_shows_partial_occupancy_for_shared_rooms(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', capacity: 10);
        $this->booking($owner, $room, '2026-09-01', '09:00', '11:00', partySize: 3);

        $segments = $this->availability->freeBusyForDay($room, '2026-09-01');
        $busy = collect($segments)->firstWhere('start', '09:00');

        $this->assertSame(3, $busy['used']);
        $this->assertSame(7, $busy['available']);
    }

    // --- Defense-in-depth: the post-write backstop, independent of the lock ---

    public function test_exceeds_capacity_catches_a_would_be_overbooking_from_a_raced_write(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', capacity: 10);

        // Simulates exactly the race the row lock exists to prevent: a
        // request reads "10 free" before anyone has written anything...
        $seenAsFree = $this->availability->availabilityForRange($room, '2026-09-01', '10:00', '12:00');
        $this->assertSame(10, $seenAsFree);

        // ...then a concurrent request "wins" and commits a party of 7...
        $this->booking($owner, $room, '2026-09-01', '10:00', '12:00', partySize: 7);

        // ...and this request proceeds to write its own party of 5 based on
        // its now-stale read of "10 free". Without a post-write check, this
        // would silently overbook the room to 12/10.
        $this->booking($owner, $room, '2026-09-01', '10:00', '12:00', partySize: 5);

        // The backstop must catch this regardless of whether the row lock
        // actually serialized the two writes.
        $this->assertTrue($this->availability->exceedsCapacity($room, '2026-09-01', '10:00', '12:00'));
    }

    public function test_exceeds_capacity_is_false_for_a_valid_state(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', capacity: 10);
        $this->booking($owner, $room, '2026-09-01', '10:00', '12:00', partySize: 7);

        $this->assertFalse($this->availability->exceedsCapacity($room, '2026-09-01', '10:00', '12:00'));
    }
}
