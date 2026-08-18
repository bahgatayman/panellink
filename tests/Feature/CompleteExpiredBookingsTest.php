<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The 'bookings:complete-expired' sweep: auto-completes exclusive-room
 * 'confirmed' bookings once their scheduled end time has passed. Shared
 * rooms are deliberately excluded (their 'confirmed' status means
 * "awaiting check-in," not "fulfilled" — see the command's own docblock).
 */
class CompleteExpiredBookingsTest extends TestCase
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

    private function booking(Owner $owner, Room $room, array $overrides = []): Booking
    {
        return $room->bookings()->create(array_merge([
            'owner_id' => $owner->id,
            'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 1,
            'booking_date' => '2026-08-18',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'price_per_hour' => $room->price_per_hour,
            'total_hours' => 1,
            'total_price' => $room->price_per_hour,
            'status' => 'confirmed',
        ], $overrides));
    }

    public function test_exclusive_room_booking_past_its_end_time_today_is_completed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 11:30:00'));
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner), [
            'start_time' => '10:00', 'end_time' => '11:00',
        ]);

        $this->artisan('bookings:complete-expired')->assertExitCode(0);

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_exclusive_room_booking_today_not_yet_ended_stays_confirmed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 10:30:00'));
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner), [
            'start_time' => '10:00', 'end_time' => '11:00',
        ]);

        $this->artisan('bookings:complete-expired');

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_exclusive_room_booking_from_a_past_date_is_completed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19 08:00:00'));
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner), [
            'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '11:00',
        ]);

        $this->artisan('bookings:complete-expired');

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_midnight_crossing_sweep_run_still_completes_a_late_booking(): void
    {
        // Booking dated yesterday, ending 23:45 — the sweep itself runs at
        // 00:10 *today*. A bare time-of-day string compare ('00:10' <
        // '23:45') would wrongly conclude this hasn't ended yet; the real
        // combined-datetime check must still catch it.
        Carbon::setTestNow(Carbon::parse('2026-08-19 00:10:00'));
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner), [
            'booking_date' => '2026-08-18', 'start_time' => '23:00', 'end_time' => '23:45',
        ]);

        $this->artisan('bookings:complete-expired');

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_shared_room_confirmed_booking_past_its_end_time_is_untouched(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 11:30:00'));
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner, 'shared', 10), [
            'start_time' => '10:00', 'end_time' => '11:00',
        ]);

        $this->artisan('bookings:complete-expired');

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_already_finalized_statuses_are_left_completely_untouched(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 12:00:00'));
        $owner = $this->owner();
        $room = $this->room($owner);

        foreach (['completed', 'cancelled', 'checked_in', 'no_show'] as $status) {
            $booking = $this->booking($owner, $room, [
                'start_time' => '10:00', 'end_time' => '11:00', 'status' => $status,
            ]);
            $originalUpdatedAt = $booking->updated_at;

            $this->artisan('bookings:complete-expired');

            $fresh = $booking->fresh();
            $this->assertSame($status, $fresh->status, "Status '{$status}' must not change.");
            $this->assertTrue($originalUpdatedAt->eq($fresh->updated_at), "Row for status '{$status}' must not be written to.");
        }
    }

    public function test_running_the_sweep_twice_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 11:30:00'));
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner), [
            'start_time' => '10:00', 'end_time' => '11:00',
        ]);

        $this->artisan('bookings:complete-expired');
        $completedAt = $booking->fresh()->updated_at;

        // A second run must be a no-op: the row is no longer 'confirmed',
        // so it falls outside the sweep's WHERE clause entirely.
        $this->artisan('bookings:complete-expired');

        $fresh = $booking->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertTrue($completedAt->eq($fresh->updated_at));
    }
}
