<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\WorkingHour;
use App\Models\Workspace;
use App\Services\BookingService;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BookingController::roomOptions() — the aggregating endpoint behind the
 * room-picker cards. Every price/availability figure it returns must
 * delegate to BookingService/AvailabilityService/BusinessHoursService, the
 * same classes checkAvailability()/store()/update() already use.
 */
class BookingRoomOptionsTest extends TestCase
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

    private function room(Owner $owner, string $type = 'meeting', float $pricePerHour = 100, int $capacity = 4): Room
    {
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);

        return Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Room '.uniqid(),
            'type' => $type, 'capacity' => $capacity, 'price_per_hour' => $pricePerHour,
        ]);
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Member', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    private function query(array $overrides = []): array
    {
        return array_merge([
            'booking_date' => today()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ], $overrides);
    }

    public function test_a_room_with_no_bookings_that_day_is_fully_available(): void
    {
        $owner = $this->owner();
        $this->room($owner);

        $response = $this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query($this->query()));

        $response->assertOk();
        $this->assertSame('free', $response->json('rooms.0.state'));
        $this->assertNull($response->json('rooms.0.reason'));
    }

    public function test_a_room_booked_at_a_different_time_the_same_day_is_partial(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        Booking::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 1, 'booking_date' => today()->addDay()->toDateString(),
            'start_time' => '16:00', 'end_time' => '17:00',
            'price_per_hour' => 100, 'total_hours' => 1, 'total_price' => 100, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query($this->query()));

        $response->assertOk();
        $this->assertSame('partial', $response->json('rooms.0.state'));
    }

    public function test_an_overlapping_booking_makes_an_exclusive_room_unavailable(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        Booking::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 1, 'booking_date' => today()->addDay()->toDateString(),
            'start_time' => '10:30', 'end_time' => '11:30',
            'price_per_hour' => 100, 'total_hours' => 1, 'total_price' => 100, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query($this->query()));

        $response->assertOk();
        $this->assertSame('unavailable', $response->json('rooms.0.state'));
        $this->assertSame('conflict', $response->json('rooms.0.reason'));
    }

    public function test_a_party_larger_than_remaining_shared_capacity_is_no_seats(): void
    {
        $owner = $this->owner();
        $this->room($owner, type: 'shared', capacity: 5);

        $response = $this->actingAs($owner, 'owner')->getJson(
            '/bookings/room-options?'.http_build_query($this->query(['party_size' => 6]))
        );

        $response->assertOk();
        $this->assertSame('unavailable', $response->json('rooms.0.state'));
        $this->assertSame('no_seats', $response->json('rooms.0.reason'));
    }

    public function test_outside_working_hours_makes_every_room_unavailable(): void
    {
        $owner = $this->owner();
        $this->room($owner);
        $date = today()->addDay();
        WorkingHour::create([
            'owner_id' => $owner->id, 'day_of_week' => $date->dayOfWeek, 'is_open' => true,
            'open_time' => '09:00:00', 'close_time' => '17:00:00',
        ]);

        $response = $this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query($this->query([
            'booking_date' => $date->toDateString(), 'start_time' => '20:00', 'end_time' => '21:00',
        ])));

        $response->assertOk();
        $this->assertSame('unavailable', $response->json('rooms.0.state'));
        $this->assertSame('outside_hours', $response->json('rooms.0.reason'));
    }

    public function test_booking_id_excludes_the_booking_being_edited(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $booking = Booking::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 1, 'booking_date' => today()->addDay()->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 100, 'total_hours' => 2, 'total_price' => 200, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query(
            $this->query(['booking_id' => $booking->id])
        ));

        $response->assertOk();
        $this->assertSame('free', $response->json('rooms.0.state'));
    }

    public function test_total_price_matches_booking_service_exactly(): void
    {
        $owner = $this->owner();
        $this->room($owner, pricePerHour: 75);

        $response = $this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query(
            $this->query(['start_time' => '09:00', 'end_time' => '11:30'])
        ));

        $expected = (new BookingService)->calculateBooking('09:00', '11:30', 75);

        $response->assertOk();
        $this->assertSame($expected['total_price'], $response->json('rooms.0.total_price'));
        $this->assertSame($expected['total_hours'], $response->json('rooms.0.total_hours'));
    }

    public function test_other_tenants_rooms_and_bookings_are_never_visible(): void
    {
        $owner = $this->owner();
        $other = $this->owner();
        $this->room($owner);
        $this->room($other);

        $response = $this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query($this->query()));

        $response->assertOk();
        $this->assertCount(1, $response->json('rooms'));
    }
}
