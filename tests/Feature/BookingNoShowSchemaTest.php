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
 * Phase 5a: schema/domain-model additions only — no booking writes,
 * check-in, no-show automation, or availability wiring yet (those are
 * Phase 5b+). These tests verify the two migrations (bookings.status is no
 * longer DB-enum-constrained; bookings.checked_in_party_size exists) and
 * the pure Booking model helpers that Phase 5b will build on.
 */
class BookingNoShowSchemaTest extends TestCase
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

    private function room(Owner $owner, string $type = 'shared', int $capacity = 10): Room
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

    private function booking(Owner $owner, Room $room, array $overrides = []): Booking
    {
        return $room->bookings()->create(array_merge([
            'owner_id' => $owner->id,
            'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 3,
            'booking_date' => today()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'price_per_hour' => 50,
            'total_hours' => 2,
            'total_price' => 100,
            'status' => 'confirmed',
        ], $overrides));
    }

    // --- Migration: status is no longer DB-enum-constrained ---

    public function test_status_column_accepts_checked_in_and_no_show_values(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);

        $checkedIn = $this->booking($owner, $room, ['status' => 'checked_in']);
        $noShow = $this->booking($owner, $room, ['status' => 'no_show']);

        $this->assertSame('checked_in', $checkedIn->fresh()->status);
        $this->assertSame('no_show', $noShow->fresh()->status);
    }

    // --- Migration: checked_in_party_size column ---

    public function test_checked_in_party_size_defaults_to_null(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);

        $booking = $this->booking($owner, $room);

        $this->assertNull($booking->fresh()->checked_in_party_size);
    }

    public function test_checked_in_party_size_is_stored_and_cast_to_integer(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);

        $booking = $this->booking($owner, $room, ['party_size' => 5, 'checked_in_party_size' => 3]);
        $fresh = $booking->fresh();

        $this->assertSame(3, $fresh->checked_in_party_size);
        $this->assertIsInt($fresh->checked_in_party_size);
        // party_size (the original reservation) must stay untouched by check-in.
        $this->assertSame(5, $fresh->party_size);
    }

    // --- Model: statusLabel()/statusColor() for the new values ---

    public function test_status_label_and_color_cover_the_new_statuses(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);

        $checkedIn = $this->booking($owner, $room, ['status' => 'checked_in']);
        $noShow = $this->booking($owner, $room, ['status' => 'no_show']);

        $this->assertSame('Checked In', $checkedIn->statusLabel());
        $this->assertSame('teal', $checkedIn->statusColor());
        $this->assertSame('No Show', $noShow->statusLabel());
        $this->assertSame('orange', $noShow->statusColor());
    }

    public function test_existing_status_label_and_color_are_unchanged(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);

        $pending = $this->booking($owner, $room, ['status' => 'pending']);

        $this->assertSame('Pending', $pending->statusLabel());
        $this->assertSame('yellow', $pending->statusColor());
    }

    // --- Model: startsAt() ---

    public function test_starts_at_combines_booking_date_and_start_time(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);

        $booking = $this->booking($owner, $room, [
            'booking_date' => '2026-09-01', 'start_time' => '14:30',
        ]);

        $this->assertSame('2026-09-01 14:30:00', $booking->startsAt()->format('Y-m-d H:i:s'));
    }

    // --- Model: isPastNoShowGrace() ---

    public function test_is_past_no_show_grace_is_false_within_the_grace_window(): void
    {
        $now = Carbon::parse('2026-08-18 10:20:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->room($owner);
        $booking = $this->booking($owner, $room, [
            'booking_date' => '2026-08-18', 'start_time' => '10:00',
        ]);

        // 20 minutes past start_time — inside the 30-minute grace period.
        $this->assertFalse($booking->isPastNoShowGrace());
    }

    public function test_is_past_no_show_grace_is_true_after_the_grace_window(): void
    {
        $now = Carbon::parse('2026-08-18 10:31:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->room($owner);
        $booking = $this->booking($owner, $room, [
            'booking_date' => '2026-08-18', 'start_time' => '10:00',
        ]);

        // 31 minutes past start_time — just past the 30-minute grace period.
        $this->assertTrue($booking->isPastNoShowGrace());
    }

    public function test_is_past_no_show_grace_is_false_before_start_time(): void
    {
        $now = Carbon::parse('2026-08-18 09:00:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->room($owner);
        $booking = $this->booking($owner, $room, [
            'booking_date' => '2026-08-18', 'start_time' => '10:00',
        ]);

        $this->assertFalse($booking->isPastNoShowGrace());
    }

    public function test_no_show_grace_minutes_constant_is_thirty(): void
    {
        $this->assertSame(30, Booking::NO_SHOW_GRACE_MINUTES);
    }

    // --- Model: noShowSeats() ---

    public function test_no_show_seats_is_null_before_check_in(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $booking = $this->booking($owner, $room, ['party_size' => 4]);

        $this->assertNull($booking->noShowSeats());
    }

    public function test_no_show_seats_reflects_the_gap_after_a_partial_check_in(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $booking = $this->booking($owner, $room, [
            'party_size' => 5, 'checked_in_party_size' => 3, 'status' => 'checked_in',
        ]);

        $this->assertSame(2, $booking->noShowSeats());
        // Original reservation size must remain visible, not overwritten.
        $this->assertSame(5, $booking->party_size);
    }

    public function test_no_show_seats_is_zero_when_everyone_checked_in(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $booking = $this->booking($owner, $room, [
            'party_size' => 4, 'checked_in_party_size' => 4, 'status' => 'checked_in',
        ]);

        $this->assertSame(0, $booking->noShowSeats());
    }

    // --- Regression: exclusive-room bookings are entirely unaffected ---

    public function test_exclusive_room_booking_lifecycle_is_unaffected_by_the_new_columns(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, 'meeting', 1);
        $booking = $this->booking($owner, $room, ['party_size' => 1]);

        $this->assertNull($booking->fresh()->checked_in_party_size);
        $this->assertSame('Confirmed', $booking->statusLabel());
        $this->assertSame('blue', $booking->statusColor());
    }
}
