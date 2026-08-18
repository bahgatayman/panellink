<?php

namespace Tests\Feature;

use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\WorkingHour;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Working Hours Phase 3: the validation-only guards added to
 * BookingController::store()/update()/checkIn() and
 * SharedSessionController::store(). Nothing about capacity/lock behavior
 * changes here — these are purely an additional, independent rejection
 * reason layered in front of the existing checks.
 */
class WorkingHoursEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Room',
            'type' => $type, 'capacity' => $capacity, 'price_per_hour' => 40,
        ]);
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Member', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    private function day(Owner $owner, int $dayOfWeek, array $overrides = []): WorkingHour
    {
        return WorkingHour::create(array_merge([
            'owner_id' => $owner->id,
            'day_of_week' => $dayOfWeek,
            'is_open' => true,
            'open_time' => '10:00:00',
            'close_time' => '22:00:00',
        ], $overrides));
    }

    // --- BookingController::store() / update() ---

    public function test_booking_within_configured_hours_is_accepted(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $user = $this->member($owner);
        $date = today()->next(Carbon::TUESDAY);
        $this->day($owner, $date->dayOfWeek, ['open_time' => '10:00:00', 'close_time' => '22:00:00']);

        $response = $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'booking_date' => $date->toDateString(), 'start_time' => '11:00', 'end_time' => '12:00',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionMissing('error');
        $this->assertSame(1, $room->bookings()->count());
    }

    public function test_booking_outside_configured_hours_is_rejected(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $user = $this->member($owner);
        $date = today()->next(Carbon::TUESDAY);
        $this->day($owner, $date->dayOfWeek, ['open_time' => '10:00:00', 'close_time' => '22:00:00']);

        $response = $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'booking_date' => $date->toDateString(), 'start_time' => '07:00', 'end_time' => '08:00',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, $room->bookings()->count());
    }

    public function test_booking_on_a_closed_day_is_rejected(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $user = $this->member($owner);
        $date = today()->next(Carbon::TUESDAY);
        $this->day($owner, $date->dayOfWeek, ['is_open' => false, 'open_time' => null, 'close_time' => null]);

        $response = $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'booking_date' => $date->toDateString(), 'start_time' => '11:00', 'end_time' => '12:00',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, $room->bookings()->count());
    }

    public function test_owner_with_no_configured_hours_is_completely_unaffected(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $user = $this->member($owner);

        $response = $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'booking_date' => today()->toDateString(), 'start_time' => '03:00', 'end_time' => '04:00',
        ]);

        $response->assertSessionMissing('error');
        $this->assertSame(1, $room->bookings()->count());
    }

    public function test_editing_a_booking_to_fall_outside_hours_is_rejected(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $user = $this->member($owner);
        $date = today()->next(Carbon::TUESDAY);
        $this->day($owner, $date->dayOfWeek, ['open_time' => '10:00:00', 'close_time' => '22:00:00']);

        $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'booking_date' => $date->toDateString(), 'start_time' => '11:00', 'end_time' => '12:00',
        ]);
        $booking = $room->bookings()->first();

        $response = $this->actingAs($owner, 'owner')->put("/bookings/{$booking->id}", [
            'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'booking_date' => $date->toDateString(), 'start_time' => '23:00', 'end_time' => '23:30',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame('11:00', $booking->fresh()->start_time);
    }

    public function test_booking_spanning_an_overnight_window_is_accepted(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $user = $this->member($owner);

        // Thursday 22:00 -> 02:00 overnight; Friday itself has no separate row.
        $thursday = today()->next(Carbon::THURSDAY);
        $friday = $thursday->copy()->addDay();
        $this->day($owner, $thursday->dayOfWeek, ['open_time' => '22:00:00', 'close_time' => '02:00:00']);

        $response = $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'booking_date' => $friday->toDateString(), 'start_time' => '00:30', 'end_time' => '01:30',
        ]);

        $response->assertSessionMissing('error');
        $this->assertSame(1, $room->bookings()->count());
    }

    // --- BookingController::checkIn() ---

    public function test_check_in_outside_working_hours_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 23:00:00')); // Tuesday, 23:00.
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', 10);
        $user = $this->member($owner);
        $this->day($owner, 2, ['open_time' => '10:00:00', 'close_time' => '22:00:00']);

        // start_time chosen close to "now" so the no-show-grace check (a
        // separate, earlier guard) doesn't also fire — this test isolates
        // the working-hours rejection specifically.
        $booking = $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $user->id, 'party_size' => 2,
            'booking_date' => '2026-08-18', 'start_time' => '22:45', 'end_time' => '23:30',
            'price_per_hour' => $room->price_per_hour, 'total_hours' => 1, 'total_price' => 40,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/check-in", ['party_size' => 2]);

        $response->assertSessionHas('error');
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_check_in_within_working_hours_succeeds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 11:00:00')); // Tuesday, 11:00.
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', 10);
        $user = $this->member($owner);
        $this->day($owner, 2, ['open_time' => '10:00:00', 'close_time' => '22:00:00']);

        $booking = $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $user->id, 'party_size' => 2,
            'booking_date' => '2026-08-18', 'start_time' => '10:45', 'end_time' => '12:00',
            'price_per_hour' => $room->price_per_hour, 'total_hours' => 1.5, 'total_price' => 60,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/check-in", ['party_size' => 2]);

        $response->assertSessionMissing('error');
        $this->assertSame('checked_in', $booking->fresh()->status);
    }

    // --- SharedSessionController::store() ---

    public function test_walk_in_session_outside_working_hours_is_rejected(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', 10);
        $user = $this->member($owner);
        $date = today()->next(Carbon::TUESDAY);
        $this->day($owner, $date->dayOfWeek, ['open_time' => '10:00:00', 'close_time' => '22:00:00']);

        $response = $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'session_date' => $date->toDateString(), 'start_time' => '23:00',
        ]);

        $response->assertSessionHas('error', __('app.session.outside_working_hours'));
        $this->assertSame(0, $room->sharedSessions()->count());
    }

    public function test_walk_in_session_within_working_hours_is_accepted(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'shared', 10);
        $user = $this->member($owner);
        $date = today()->next(Carbon::TUESDAY);
        $this->day($owner, $date->dayOfWeek, ['open_time' => '10:00:00', 'close_time' => '22:00:00']);

        $response = $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'session_date' => $date->toDateString(), 'start_time' => '11:00',
        ]);

        $response->assertSessionMissing('error');
        $this->assertSame(1, $room->sharedSessions()->count());
    }
}
