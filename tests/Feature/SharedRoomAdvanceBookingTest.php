<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Room;
use App\Models\Sale;
use App\Models\SharedSession;
use App\Models\Workspace;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5b: concurrency-correct writes for shared-room advance booking.
 * Covers the BookingController::store()/update() unblock, the
 * SharedSessionController::store() rewrite against the unified "right now"
 * formula, the check-in endpoint, and the updateStatus()/addItem()/
 * removeItem() guards. Shared-room advance bookings are no longer
 * redirected away — this is the phase that actually turns that on.
 */
class SharedRoomAdvanceBookingTest extends TestCase
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

    private function sharedRoom(Owner $owner, int $capacity = 10): Room
    {
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);

        return Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Lounge',
            'type' => 'shared', 'capacity' => $capacity, 'price_per_hour' => 40,
        ]);
    }

    private function member(Owner $owner, string $name = 'Member'): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => $name, 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    private function confirmedBooking(Owner $owner, Room $room, array $overrides = []): Booking
    {
        return $room->bookings()->create(array_merge([
            'owner_id' => $owner->id,
            'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 4,
            'booking_date' => today()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'price_per_hour' => 40,
            'total_hours' => 2,
            'total_price' => 80,
            'status' => 'confirmed',
        ], $overrides));
    }

    // --- A. Advance booking write path ---

    public function test_shared_room_can_be_advance_booked(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner);
        $member = $this->member($owner);

        $response = $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'booking_date' => today()->addDay()->toDateString(),
            'start_time' => '14:00', 'end_time' => '16:00',
            'party_size' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $booking = Booking::where('room_id', $room->id)->firstOrFail();
        $this->assertSame(5, $booking->party_size);
        $this->assertSame('confirmed', $booking->status);
    }

    public function test_shared_room_advance_booking_rejects_a_party_larger_than_remaining_capacity(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 6);
        $this->confirmedBooking($owner, $room, [
            'party_size' => 4, 'booking_date' => today()->addDay()->toDateString(),
        ]);

        $response = $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'booking_date' => today()->addDay()->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'party_size' => 3,
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(1, Booking::where('room_id', $room->id)->count());
    }

    public function test_shared_room_advance_booking_can_be_edited(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner);
        $booking = $this->confirmedBooking($owner, $room, ['booking_date' => today()->addDay()->toDateString()]);

        $response = $this->actingAs($owner, 'owner')->put("/bookings/{$booking->id}", [
            'room_id' => $room->id, 'hotspot_user_id' => $booking->hotspot_user_id,
            'booking_date' => today()->addDay()->toDateString(),
            'start_time' => '13:00', 'end_time' => '15:00',
            'party_size' => 6,
        ]);

        $response->assertRedirect("/bookings/{$booking->id}")->assertSessionHas('success');
        $fresh = $booking->fresh();
        $this->assertSame(6, $fresh->party_size);
        $this->assertTrue(str_starts_with((string) $fresh->start_time, '13:00'));
    }

    public function test_second_overlapping_advance_booking_is_rejected_after_the_first_fills_the_room(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 5);
        $date = today()->addDay()->toDateString();

        $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner, 'First')->id,
            'booking_date' => $date, 'start_time' => '10:00', 'end_time' => '12:00', 'party_size' => 5,
        ])->assertSessionHas('success');

        $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner, 'Second')->id,
            'booking_date' => $date, 'start_time' => '10:30', 'end_time' => '11:30', 'party_size' => 1,
        ])->assertSessionHas('error');

        $this->assertSame(1, Booking::where('room_id', $room->id)->count());
    }

    // --- B. Walk-in sessions now respect overlapping advance bookings ---

    public function test_walk_in_session_is_blocked_by_an_overlapping_advance_booking(): void
    {
        $now = Carbon::parse('2026-08-18 10:30:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 6);
        // Reserves 5 of 6 seats for a window that contains "now".
        $this->confirmedBooking($owner, $room, [
            'party_size' => 5, 'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        // A walk-in party of 2 doesn't fit in the 1 remaining seat.
        $response = $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'session_date' => $now->toDateString(), 'start_time' => $now->format('H:i'),
            'party_size' => 2,
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, SharedSession::where('room_id', $room->id)->count());
    }

    public function test_walk_in_session_succeeds_when_it_fits_around_an_advance_booking(): void
    {
        $now = Carbon::parse('2026-08-18 10:30:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 6);
        $this->confirmedBooking($owner, $room, [
            'party_size' => 4, 'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        $response = $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'session_date' => $now->toDateString(), 'start_time' => $now->format('H:i'),
            'party_size' => 2,
        ]);

        $response->assertRedirect(route('shared-sessions.index'));
        $this->assertSame(1, SharedSession::where('room_id', $room->id)->where('status', 'open')->count());
    }

    public function test_advance_booking_scheduled_later_today_does_not_block_a_walk_in_now(): void
    {
        $now = Carbon::parse('2026-08-18 09:00:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 4);
        // Reserves all 4 seats, but for 18:00 tonight — not now.
        $this->confirmedBooking($owner, $room, [
            'party_size' => 4, 'booking_date' => '2026-08-18', 'start_time' => '18:00', 'end_time' => '20:00',
        ]);

        $response = $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'session_date' => $now->toDateString(), 'start_time' => $now->format('H:i'),
            'party_size' => 4,
        ]);

        $response->assertRedirect(route('shared-sessions.index'));
        $this->assertSame(1, SharedSession::where('room_id', $room->id)->where('status', 'open')->count());
    }

    // --- C. usedCapacityNow()/availableNow() correctness ---

    public function test_checked_in_booking_is_not_double_counted_against_its_own_session(): void
    {
        $now = Carbon::parse('2026-08-18 10:30:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 6);
        $booking = $this->confirmedBooking($owner, $room, [
            'party_size' => 4, 'status' => 'checked_in', 'checked_in_party_size' => 4,
            'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);
        SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $booking->hotspot_user_id,
            'party_size' => 4, 'session_date' => '2026-08-18', 'start_time' => '10:00',
            'opened_at' => $now, 'status' => 'open', 'booking_id' => $booking->id,
        ]);

        $availability = app(AvailabilityService::class);

        // 4 used (via the session only, not also via the checked_in booking row) — 2 free, not -2.
        $this->assertSame(4, $availability->usedCapacityNow($room));
        $this->assertSame(2, $availability->availableNow($room));
    }

    public function test_a_confirmed_booking_past_its_grace_period_does_not_block_a_walk_in(): void
    {
        $now = Carbon::parse('2026-08-18 10:35:00'); // 35 min past a 10:00 start — past the 30-min grace.
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 4);
        $this->confirmedBooking($owner, $room, [
            'party_size' => 4, 'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        // The sweep hasn't run — status is still 'confirmed' — but the live
        // check must still free the seats for a walk-in.
        $response = $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'session_date' => $now->toDateString(), 'start_time' => $now->format('H:i'),
            'party_size' => 4,
        ]);

        $response->assertRedirect(route('shared-sessions.index'));
    }

    public function test_a_confirmed_booking_within_grace_still_blocks_a_walk_in(): void
    {
        $now = Carbon::parse('2026-08-18 10:20:00'); // 20 min past start — still within grace.
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 4);
        $this->confirmedBooking($owner, $room, [
            'party_size' => 4, 'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        $response = $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'session_date' => $now->toDateString(), 'start_time' => $now->format('H:i'),
            'party_size' => 4,
        ]);

        $response->assertSessionHas('error');
    }

    // --- D. Check-in endpoint ---

    public function test_check_in_creates_a_session_and_updates_the_booking(): void
    {
        $now = Carbon::parse('2026-08-18 10:05:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner);
        $booking = $this->confirmedBooking($owner, $room, [
            'party_size' => 5, 'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/check-in", ['party_size' => 5]);

        $response->assertRedirect(route('shared-sessions.index'))->assertSessionHas('success');

        $fresh = $booking->fresh();
        $this->assertSame('checked_in', $fresh->status);
        $this->assertSame(5, $fresh->checked_in_party_size);
        $this->assertSame(5, $fresh->party_size); // original reservation size untouched

        $session = SharedSession::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame(5, $session->party_size);
        $this->assertSame('open', $session->status);
        $this->assertSame($now->toDateTimeString(), $session->opened_at->toDateTimeString());
    }

    public function test_check_in_rejects_exclusive_room_bookings(): void
    {
        $owner = $this->owner();
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);
        $room = Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Meeting Room',
            'type' => 'meeting', 'capacity' => 1, 'price_per_hour' => 50,
        ]);
        $booking = $this->confirmedBooking($owner, $room, ['party_size' => 1]);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/check-in", ['party_size' => 1]);

        $response->assertSessionHas('error');
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(0, SharedSession::count());
    }

    public function test_check_in_rejects_a_booking_that_is_not_confirmed(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner);
        $booking = $this->confirmedBooking($owner, $room, ['status' => 'cancelled']);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/check-in", ['party_size' => 2]);

        $response->assertSessionHas('error');
        $this->assertSame(0, SharedSession::count());
    }

    public function test_check_in_rejects_a_reservation_past_its_grace_period(): void
    {
        $now = Carbon::parse('2026-08-18 10:31:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner);
        $booking = $this->confirmedBooking($owner, $room, [
            'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/check-in", ['party_size' => 4]);

        $response->assertSessionHas('error');
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_check_in_rejects_a_headcount_that_no_longer_fits(): void
    {
        $now = Carbon::parse('2026-08-18 10:05:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 5);
        $booking = $this->confirmedBooking($owner, $room, [
            'party_size' => 3, 'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);
        // Another walk-in already took 4 of the 5 seats since the reservation was made.
        SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner, 'Walkin')->id,
            'party_size' => 4, 'session_date' => '2026-08-18', 'start_time' => '10:00',
            'opened_at' => $now, 'status' => 'open',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/check-in", ['party_size' => 3]);

        $response->assertSessionHas('error');
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(1, SharedSession::where('room_id', $room->id)->count());
    }

    public function test_partial_check_in_frees_the_no_show_seats_for_others(): void
    {
        $now = Carbon::parse('2026-08-18 10:05:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 5);
        $booking = $this->confirmedBooking($owner, $room, [
            'party_size' => 5, 'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        // Only 2 of the reserved 5 actually show up.
        $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/check-in", ['party_size' => 2])
            ->assertSessionHas('success');

        $this->assertSame(3, $booking->fresh()->noShowSeats());

        // The other 3 seats are now free for a walk-in.
        $response = $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner, 'Walkin')->id,
            'session_date' => $now->toDateString(), 'start_time' => $now->format('H:i'),
            'party_size' => 3,
        ]);

        $response->assertRedirect(route('shared-sessions.index'));
    }

    public function test_concurrent_check_in_attempts_only_one_succeeds(): void
    {
        $now = Carbon::parse('2026-08-18 10:05:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner);
        $booking = $this->confirmedBooking($owner, $room, [
            'party_size' => 3, 'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        // Sequential calls simulate the race's outcome: the second request
        // must see the first's committed status change and be rejected,
        // via the same atomic guarded-update pattern already verified for
        // SharedSessionController::close()'s double-close guard.
        $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/check-in", ['party_size' => 3])
            ->assertSessionHas('success');

        $second = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/check-in", ['party_size' => 3]);

        $second->assertSessionHas('error');
        $this->assertSame(1, SharedSession::where('booking_id', $booking->id)->count());
    }

    public function test_early_check_in_is_allowed_when_capacity_permits(): void
    {
        $now = Carbon::parse('2026-08-18 08:00:00'); // 2 hours before the reservation's 10:00 start.
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 5);
        $booking = $this->confirmedBooking($owner, $room, [
            'party_size' => 3, 'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/check-in", ['party_size' => 3]);

        $response->assertSessionHas('success');
        $this->assertSame('checked_in', $booking->fresh()->status);
    }

    // --- E. updateStatus() guard ---

    public function test_shared_room_confirmed_booking_cannot_be_marked_completed_directly(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner);
        $booking = $this->confirmedBooking($owner, $room);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/status", ['status' => 'completed']);

        $response->assertSessionHas('error');
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_shared_room_confirmed_booking_can_still_be_cancelled(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner);
        $booking = $this->confirmedBooking($owner, $room);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/status", ['status' => 'cancelled']);

        $response->assertSessionHas('success');
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_exclusive_room_completed_transition_is_unaffected(): void
    {
        $owner = $this->owner();
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);
        $room = Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Meeting Room',
            'type' => 'meeting', 'capacity' => 1, 'price_per_hour' => 50,
        ]);
        $booking = $this->confirmedBooking($owner, $room, ['party_size' => 1]);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/status", ['status' => 'completed']);

        $response->assertSessionHas('success');
        $this->assertSame('completed', $booking->fresh()->status);
    }

    // --- F. addItem()/removeItem() guard ---

    public function test_products_cannot_be_added_to_a_pending_shared_room_booking(): void
    {
        $owner = $this->owner();
        $owner->enableFeature('sales');
        $room = $this->sharedRoom($owner);
        $booking = $this->confirmedBooking($owner, $room);

        $product = Product::create([
            'owner_id' => $owner->id, 'name' => 'Coffee', 'price' => 10, 'is_active' => true, 'type' => 'product',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/items", ['product_id' => $product->id, 'quantity' => 1]);

        $response->assertSessionHas('error');
        $this->assertSame(0, Sale::where('booking_id', $booking->id)->count());
    }

    public function test_products_can_be_added_once_checked_in(): void
    {
        $now = Carbon::parse('2026-08-18 10:05:00');
        Carbon::setTestNow($now);

        $owner = $this->owner();
        $owner->enableFeature('sales');
        $room = $this->sharedRoom($owner);
        $booking = $this->confirmedBooking($owner, $room, [
            'booking_date' => '2026-08-18', 'start_time' => '10:00', 'end_time' => '12:00',
        ]);

        $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/check-in", ['party_size' => 4])
            ->assertSessionHas('success');

        $product = Product::create([
            'owner_id' => $owner->id, 'name' => 'Coffee', 'price' => 10, 'is_active' => true, 'type' => 'product',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->post("/bookings/{$booking->id}/items", ['product_id' => $product->id, 'quantity' => 2]);

        $response->assertSessionHas('success');
        $this->assertSame(1, Sale::where('booking_id', $booking->id)->count());
    }
}
