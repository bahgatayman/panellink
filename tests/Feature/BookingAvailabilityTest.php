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

/**
 * Feature-level coverage for Phase 1 of the availability engine: the guarded
 * store()/update() flow, and checkAvailability()'s room-type-awareness fix.
 */
class BookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
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

    private function room(Owner $owner, string $type = 'meeting', int $capacity = 4): Room
    {
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);

        return Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Room A',
            'type' => $type, 'capacity' => $capacity, 'price_per_hour' => 50,
        ]);
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Member', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    // --- Exclusive-room behavior stays byte-for-byte unchanged ---

    public function test_exclusive_room_booking_still_works_exactly_as_before(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'meeting');
        $member = $this->member($owner);

        $this->actingAs($owner, 'owner')
            ->post('/bookings', [
                'room_id' => $room->id, 'hotspot_user_id' => $member->id,
                'booking_date' => today()->toDateString(), 'start_time' => '10:00', 'end_time' => '12:00',
            ])
            ->assertRedirect();

        $this->assertSame(1, Booking::where('room_id', $room->id)->count());
    }

    public function test_overlapping_exclusive_room_booking_is_still_rejected(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'meeting');
        $member = $this->member($owner);

        $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'booking_date' => today()->toDateString(), 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        $this->actingAs($owner, 'owner')
            ->post('/bookings', [
                'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
                'booking_date' => today()->toDateString(), 'start_time' => '11:00', 'end_time' => '13:00',
            ])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, Booking::where('room_id', $room->id)->count());
    }

    // --- The guarded flow rejects a second request that no longer fits ---

    public function test_second_overlapping_request_is_rejected_after_the_first_commits(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'meeting', capacity: 1);

        // Two sequential requests, deliberately calling store() twice back to
        // back rather than truly in parallel — PHPUnit is single-process, so
        // this proves the guard's LOGIC is correct (the second request
        // correctly sees the first's committed state and is rejected), not
        // that lockForUpdate() itself serializes concurrent requests on the
        // production DB engine. That claim rests on Laravel's MySQL grammar
        // (verified directly: compileLock() emits a genuine `for update`)
        // plus the engine-independent exceedsCapacity() backstop covered in
        // AvailabilityServiceTest.
        $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'booking_date' => today()->toDateString(), 'start_time' => '10:00', 'end_time' => '12:00',
        ])->assertRedirect();
        $this->assertSame(1, Booking::where('room_id', $room->id)->count());

        $second = $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'booking_date' => today()->toDateString(), 'start_time' => '10:30', 'end_time' => '11:30',
        ]);
        $second->assertSessionHas('error');

        $this->assertSame(1, Booking::where('room_id', $room->id)->count());
    }

    public function test_shared_room_admits_a_second_party_within_capacity(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', capacity: 10);
        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 3, 'booking_date' => today()->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 100, 'status' => 'confirmed',
        ]);

        // Exercises the read-side capacity math directly (the HTTP write
        // path for shared-room advance bookings is covered separately in
        // BookingSharedRoomAdvanceTest, Phase 5b).
        $availability = app(AvailabilityService::class);
        $remaining = $availability->availabilityForRange($room, today()->toDateString(), '10:00', '12:00');

        $this->assertSame(7, $remaining);
    }

    // --- checkAvailability() room-type-awareness fix ---

    public function test_check_availability_is_correct_for_a_shared_room(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', capacity: 10);
        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 8, 'booking_date' => today()->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 100, 'status' => 'confirmed',
        ]);

        // 8/10 used — a party of 3 no longer fits (only 2 free), a party of 2 does.
        $this->actingAs($owner, 'owner')
            ->getJson('/bookings/check-availability?'.http_build_query([
                'room_id' => $room->id, 'booking_date' => today()->toDateString(),
                'start_time' => '10:00', 'end_time' => '12:00', 'party_size' => 3,
            ]))
            ->assertOk()->assertJson(['available' => false, 'remaining' => 2]);

        $this->actingAs($owner, 'owner')
            ->getJson('/bookings/check-availability?'.http_build_query([
                'room_id' => $room->id, 'booking_date' => today()->toDateString(),
                'start_time' => '10:00', 'end_time' => '12:00', 'party_size' => 2,
            ]))
            ->assertOk()->assertJson(['available' => true, 'remaining' => 2]);
    }

    public function test_check_availability_still_correct_for_exclusive_rooms(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'meeting', capacity: 1);

        $this->actingAs($owner, 'owner')
            ->getJson('/bookings/check-availability?'.http_build_query([
                'room_id' => $room->id, 'booking_date' => today()->toDateString(),
                'start_time' => '10:00', 'end_time' => '12:00',
            ]))
            ->assertOk()->assertJson(['available' => true]);
    }
}
