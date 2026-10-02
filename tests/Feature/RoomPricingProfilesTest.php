<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomPlan;
use App\Models\RoomPricingProfile;
use App\Models\SharedSession;
use App\Models\Staff;
use App\Models\Workspace;
use App\Services\AnalyticsPeriod;
use App\Services\RevenueAnalyticsService;
use App\Services\RoomPricingService;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Room Pricing Profiles: optional named hourly rates per room, chosen per
 * booking/session, priced through RoomPricingService (and, for shared rooms,
 * SharedSessionBillingService with the room's billing unit + grace buffer).
 */
class RoomPricingProfilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------------ fixtures

    private function owner(): Owner
    {
        $plan = Plan::create([
            'name' => 'Test', 'slug' => 'test-'.uniqid(), 'max_members' => 100, 'price_per_month' => 0,
            'is_active' => true, 'sort_order' => 1, 'features' => ['workspace', 'booking', 'sales'],
            'max_workspaces' => 0, 'max_rooms' => 0, 'max_products' => 0,
        ]);
        $owner = Owner::create([
            'name' => 'Owner', 'email' => 'o'.uniqid().'@t.local', 'password' => 'secret123', 'business_name' => 'Space',
            'plan_id' => $plan->id, 'is_active' => true, 'subscription_starts_at' => now(), 'subscription_expires_at' => now()->addMonth(),
        ]);
        foreach ($plan->features as $key) {
            $owner->enableFeature($key);
        }

        return $owner;
    }

    private function room(Owner $owner, array $attrs = []): Room
    {
        $ws = Workspace::firstOrCreate(['owner_id' => $owner->id, 'name' => 'Main']);

        return Room::create(array_merge([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Room '.uniqid(), 'type' => 'meeting',
            'capacity' => 6, 'price_per_hour' => 100,
        ], $attrs));
    }

    private function profile(Room $room, string $name, float $rate, bool $active = true): RoomPricingProfile
    {
        return RoomPricingProfile::create(['owner_id' => $room->owner_id, 'room_id' => $room->id, 'name' => $name, 'price_per_hour' => $rate, 'is_active' => $active]);
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create(['owner_id' => $owner->id, 'name' => 'Cust', 'phone' => '010'.rand(10000000, 99999999), 'password' => 'pass1234']);
    }

    private function book(Owner $owner, Room $room, ?RoomPricingProfile $profile, string $start = '10:00', string $end = '13:00', array $extra = [])
    {
        return $this->actingAs($owner, 'owner')->postJson('/bookings', array_merge([
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'booking_date' => today()->addDay()->toDateString(), 'start_time' => $start, 'end_time' => $end,
            'room_pricing_profile_id' => $profile?->id,
        ], $extra));
    }

    private function lastBooking(Owner $owner): Booking
    {
        return Booking::where('owner_id', $owner->id)->latest('id')->firstOrFail();
    }

    // ------------------------------------------------------------------ pricing

    public function test_default_price_without_a_profile(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $this->profile($room, 'Photography', 700);

        $this->book($owner, $room, null)->assertOk();
        $b = $this->lastBooking($owner);
        $this->assertEquals(300.0, (float) $b->total_price, '3h × 100');
        $this->assertNull($b->room_pricing_profile_id);
        $this->assertNull($b->pricing_profile_name);
    }

    public function test_each_profile_prices_the_booking_at_its_own_rate(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        foreach (['Cinema' => 500, 'Photography' => 700, 'Event' => 1000] as $name => $rate) {
            $profile = $this->profile($room, $name, $rate);
            $this->book($owner, $room, $profile, '10:00', '13:00', ['booking_date' => today()->addDays(1 + $rate % 7)->toDateString()])->assertOk();
            $b = $this->lastBooking($owner);
            $this->assertEquals($rate * 3, (float) $b->total_price, "{$name}: 3h × {$rate}");
            $this->assertEquals($rate, (float) $b->price_per_hour);
            $this->assertSame($profile->id, $b->room_pricing_profile_id);
            $this->assertSame($name, $b->pricing_profile_name);
            $this->assertStringContainsString($name, $b->pricing_note);
        }
    }

    public function test_room_options_quote_every_active_profile_server_side(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $this->profile($room, 'Photography', 700);
        $this->profile($room, 'Old', 50, false);

        $rooms = collect($this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query([
            'booking_date' => today()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '13:00',
        ]))->assertOk()->json('rooms'))->keyBy('id');

        $profiles = $rooms[$room->id]['profiles'];
        $this->assertCount(1, $profiles, 'Inactive profiles are not offered.');
        $this->assertSame('Photography', $profiles[0]['name']);
        $this->assertEquals(2100, $profiles[0]['total_price']);
        $this->assertEquals(300, $rooms[$room->id]['total_price'], 'Default quote unchanged.');
    }

    public function test_a_rule_priced_room_with_a_profile_bills_hourly_at_the_profile_rate(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, [
            'pricing_model' => 'duration',
            'pricing_rules' => ['durations' => [['minutes' => 60], ['minutes' => 240]], 'prices' => [[150, 400]]],
        ]);
        $profile = $this->profile($room, 'Event', 1000);

        $this->book($owner, $room, null)->assertOk();
        $this->assertEquals(400.0, (float) $this->lastBooking($owner)->total_price, 'Default: 4h package covers 3h.');

        $this->book($owner, $room, $profile, '14:00', '18:00')->assertOk();
        $this->assertEquals(4000.0, (float) $this->lastBooking($owner)->total_price, 'Profile: 4h × 1000.');
    }

    // ------------------------------------------------------------------ snapshot

    public function test_changing_a_profile_never_changes_existing_bookings_or_revenue(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $profile = $this->profile($room, 'Photography', 700);

        $this->book($owner, $room, $profile)->assertOk();
        $booking = $this->lastBooking($owner);
        $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/payment", ['amount' => 2100]);
        $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/status", ['status' => 'completed']);

        $profile->update(['price_per_hour' => 800, 'name' => 'Photo Pro']);
        $profile->delete();

        $booking->refresh();
        $this->assertEquals(2100.0, (float) $booking->total_price);
        $this->assertEquals(700.0, (float) $booking->price_per_hour);
        $this->assertSame('Photography', $booking->pricing_profile_name, 'The name snapshot survives rename and deletion.');
        $this->assertNull($booking->room_pricing_profile_id);

        $period = AnalyticsPeriod::custom(today()->addDay(), today()->addDay());
        $this->assertEquals(2100.0, app(RevenueAnalyticsService::class)->bookingRevenue($owner, $period));
    }

    // ------------------------------------------------------------------ validation

    public function test_inactive_foreign_and_cross_tenant_profiles_are_rejected(): void
    {
        $owner = $this->owner();
        $roomA = $this->room($owner);
        $roomB = $this->room($owner);
        $inactive = $this->profile($roomA, 'Old', 50, false);
        $onA = $this->profile($roomA, 'Photography', 700);
        $other = $this->owner();
        $theirs = $this->profile($this->room($other), 'Theirs', 1);

        $msg = __('app.pricing_profiles.errors.not_available');
        $this->book($owner, $roomA, $inactive)->assertStatus(422)->assertJson(['message' => $msg]);
        $this->book($owner, $roomB, $onA)->assertStatus(422)->assertJson(['message' => $msg]);
        $this->book($owner, $roomA, $theirs)->assertStatus(422)->assertJson(['message' => $msg]);
        $this->assertSame(0, Booking::where('owner_id', $owner->id)->count());
    }

    public function test_a_plan_and_a_profile_cannot_be_combined(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $profile = $this->profile($room, 'Photography', 700);
        $plan = RoomPlan::create(['owner_id' => $owner->id, 'room_id' => $room->id, 'name' => 'Pack', 'people' => 1,
            'duration_minutes' => 120, 'is_full_day' => false, 'price' => 150, 'sort_order' => 0]);

        $this->book($owner, $room, $profile, '10:00', '12:00', ['room_plan_id' => $plan->id])
            ->assertStatus(422)->assertJson(['message' => __('app.pricing_profiles.errors.with_plan')]);

        $this->book($owner, $room, null, '10:00', '12:00', ['room_plan_id' => $plan->id])->assertOk();
        $this->assertEquals(150.0, (float) $this->lastBooking($owner)->total_price, 'Plans keep their fixed price.');
    }

    // ------------------------------------------------------------------ editing

    public function test_editing_keeps_switches_or_drops_the_profile(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $other = $this->room($owner);
        $photo = $this->profile($room, 'Photography', 700);
        $event = $this->profile($room, 'Event', 1000);

        $this->book($owner, $room, $photo)->assertOk();
        $booking = $this->lastBooking($owner);
        $base = ['hotspot_user_id' => $booking->hotspot_user_id, 'booking_date' => $booking->booking_date->toDateString(), 'start_time' => '10:00', 'end_time' => '13:00'];

        // Form without the field → keeps its profile, even after it was deactivated.
        $photo->update(['is_active' => false]);
        $this->actingAs($owner, 'owner')->put("/bookings/{$booking->id}", array_merge($base, ['room_id' => $room->id, 'end_time' => '12:00']))->assertRedirect("/bookings/{$booking->id}");
        $this->assertEquals(1400.0, (float) $booking->fresh()->total_price, '2h × 700 (kept profile)');

        // Switch to another profile.
        $this->actingAs($owner, 'owner')->put("/bookings/{$booking->id}", $base + ['room_id' => $room->id, 'room_pricing_profile_id' => $event->id])->assertRedirect();
        $this->assertEquals(3000.0, (float) $booking->fresh()->total_price);

        // Change room while still sending the old room's profile → refused, booking unchanged.
        $this->actingAs($owner, 'owner')->put("/bookings/{$booking->id}", $base + ['room_id' => $other->id, 'room_pricing_profile_id' => $event->id])
            ->assertSessionHas('error', __('app.pricing_profiles.errors.not_available'));
        $this->assertSame($room->id, $booking->fresh()->room_id);

        // Change room (form reset the choice) → default pricing of the new room.
        $this->actingAs($owner, 'owner')->put("/bookings/{$booking->id}", $base + ['room_id' => $other->id, 'room_pricing_profile_id' => ''])->assertRedirect();
        $fresh = $booking->fresh();
        $this->assertSame($other->id, $fresh->room_id);
        $this->assertNull($fresh->room_pricing_profile_id);
        $this->assertEquals(300.0, (float) $fresh->total_price);
    }

    // ------------------------------------------------------------------ shared areas

    public function test_shared_session_with_a_profile_bills_at_its_rate_with_the_grace_buffer(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, ['type' => 'shared', 'capacity' => 8, 'price_per_hour' => 10, 'billing_unit' => 'hour', 'billing_buffer_minutes' => 15]);
        $profile = $this->profile($room, 'Photography', 100);
        $member = $this->member($owner);

        $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $member->id, 'room_pricing_profile_id' => $profile->id,
            'session_date' => now()->toDateString(), 'start_time' => now()->format('H:i'),
        ])->assertRedirect();
        $session = SharedSession::where('owner_id', $owner->id)->firstOrFail();
        $this->assertEquals(100.0, (float) $session->billed_price_per_hour);
        $this->assertSame('Photography', $session->pricing_profile_name);
        $this->assertNull($session->pricing_snapshot);

        // 75 minutes in: still one hour thanks to the 15-minute grace.
        $now = now()->addMinutes(75);
        $session->update(['opened_at' => now()]);
        Carbon::setTestNow($now);

        $this->assertEquals(100.0, app(RoomPricingService::class)->quoteSession($session->fresh(), now())->totalPrice);
        $this->actingAs($owner, 'owner')->get('/active-sessions')->assertOk()->assertSee('Photography');
        $this->actingAs($owner, 'owner')->getJson("/shared-sessions/{$session->id}/close-preview")->assertOk()->assertJson(['total_price' => '100.00']);

        $profile->update(['price_per_hour' => 999]); // never re-prices an open session
        $this->actingAs($owner, 'owner')->postJson("/shared-sessions/{$session->id}/close")->assertOk();

        $session->refresh();
        $this->assertEquals(100.0, (float) $session->total_price);
        $booking = Booking::findOrFail($session->booking_id);
        $this->assertEquals(100.0, (float) $booking->amount_paid);
        $this->assertSame('Photography', $booking->pricing_profile_name);
        $this->assertSame($profile->id, $booking->room_pricing_profile_id);
    }

    public function test_one_minute_past_the_buffer_charges_the_next_hour_at_the_profile_rate(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, ['type' => 'shared', 'capacity' => 8, 'price_per_hour' => 10, 'billing_unit' => 'hour', 'billing_buffer_minutes' => 15]);
        $profile = $this->profile($room, 'Photography', 100);
        $now = Carbon::parse('2026-08-01 12:00:00');
        $session = SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'session_date' => $now->toDateString(), 'start_time' => '10:44', 'opened_at' => $now->copy()->subMinutes(76), 'status' => 'open',
            'billing_unit' => 'hour', 'billing_buffer_minutes' => 15, 'billed_price_per_hour' => 100,
            'room_pricing_profile_id' => $profile->id, 'pricing_profile_name' => 'Photography',
        ]);
        Carbon::setTestNow($now);

        $this->actingAs($owner, 'owner')->postJson("/shared-sessions/{$session->id}/close")->assertOk();
        $this->assertEquals(200.0, (float) $session->fresh()->total_price);
    }

    public function test_check_in_uses_the_rate_the_reservation_was_booked_at(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, ['type' => 'shared', 'capacity' => 8, 'price_per_hour' => 10, 'billing_unit' => 'hour']);
        $profile = $this->profile($room, 'Photography', 100);
        Carbon::setTestNow(today()->setTime(9, 50));

        $this->book($owner, $room, $profile, '10:00', '12:00', ['booking_date' => today()->toDateString()])->assertOk();
        $booking = $this->lastBooking($owner);
        $this->assertEquals(100.0, (float) $booking->price_per_hour);

        $profile->update(['price_per_hour' => 150]);
        $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/check-in", ['party_size' => 1])->assertRedirect();

        $session = SharedSession::where('booking_id', $booking->id)->firstOrFail();
        $this->assertEquals(100.0, (float) $session->billed_price_per_hour);
        $this->assertSame('Photography', $session->pricing_profile_name);
    }

    // ------------------------------------------------------------------ management

    private function roomPayload(array $profiles, array $over = []): array
    {
        return array_merge([
            'name' => 'Studio', 'type' => 'meeting', 'capacity' => 6, 'price_per_hour' => 100, 'pricing_model' => 'hourly',
            'pricing_profiles' => json_encode($profiles),
        ], $over);
    }

    public function test_owner_manages_profiles_from_the_room_form(): void
    {
        $owner = $this->owner();
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);

        $this->actingAs($owner, 'owner')->post("/workspaces/{$ws->id}/rooms", $this->roomPayload([
            ['name' => 'Cinema', 'price_per_hour' => 500], ['name' => 'Photography', 'price_per_hour' => 700],
        ]))->assertRedirect();
        $room = Room::where('owner_id', $owner->id)->firstOrFail();
        $this->assertSame(['Cinema', 'Photography'], $room->pricingProfiles()->pluck('name')->all());
        [$cinema, $photo] = $room->pricingProfiles()->get()->all();

        // Used profile removed → deactivated; unused removed → deleted; edits + new ones saved.
        $this->book($owner, $room, $photo)->assertOk();
        $this->actingAs($owner, 'owner')->put("/workspaces/{$ws->id}/rooms/{$room->id}", $this->roomPayload([
            ['id' => $cinema->id, 'name' => 'Cinema Night', 'price_per_hour' => 550, 'is_active' => true],
            ['name' => 'Event', 'price_per_hour' => 1000],
        ]))->assertRedirect();

        $this->assertSame(['Cinema Night', 'Photography', 'Event'], $room->pricingProfiles()->orderBy('id')->pluck('name')->all());
        $this->assertEquals(550, (float) $cinema->fresh()->price_per_hour);
        $this->assertFalse($photo->fresh()->is_active, 'Used by a booking → kept, deactivated.');
        $this->assertSame(['Cinema Night', 'Event'], $room->activePricingProfiles()->pluck('name')->all());

        $this->actingAs($owner, 'owner')->put("/workspaces/{$ws->id}/rooms/{$room->id}", $this->roomPayload([]))->assertRedirect();
        $this->assertSame(['Photography'], $room->pricingProfiles()->pluck('name')->all(), 'Unused ones deleted; the used one stays (inactive).');

        $this->actingAs($owner, 'owner')->get("/workspaces/{$ws->id}/rooms/{$room->id}/edit")->assertOk()->assertSee(__('app.pricing_profiles.section'));
    }

    public function test_invalid_profiles_are_rejected(): void
    {
        $owner = $this->owner();
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);
        $post = fn ($profiles) => $this->actingAs($owner, 'owner')->post("/workspaces/{$ws->id}/rooms", $this->roomPayload($profiles));

        $post([['name' => '', 'price_per_hour' => 10]])->assertSessionHasErrors('pricing_profiles');
        $post([['name' => 'X', 'price_per_hour' => -5]])->assertSessionHasErrors('pricing_profiles');
        $post([['name' => 'X', 'price_per_hour' => 'abc']])->assertSessionHasErrors('pricing_profiles');
        $post([['name' => 'Event', 'price_per_hour' => 10], ['name' => 'event', 'price_per_hour' => 20]])->assertSessionHasErrors('pricing_profiles');
        $this->assertSame(0, Room::where('owner_id', $owner->id)->count());

        // Same name allowed when one of them is inactive.
        $post([['name' => 'Event', 'price_per_hour' => 10], ['name' => 'event', 'price_per_hour' => 20, 'is_active' => false]])->assertRedirect();
    }

    public function test_tenant_isolation_and_permissions_for_management(): void
    {
        $owner = $this->owner();
        $other = $this->owner();
        $theirs = $this->room($other);
        $theirProfile = $this->profile($theirs, 'Theirs', 300);

        $this->actingAs($owner, 'owner')
            ->put("/workspaces/{$theirs->workspace_id}/rooms/{$theirs->id}", $this->roomPayload([['id' => $theirProfile->id, 'name' => 'Hacked', 'price_per_hour' => 1]]))
            ->assertNotFound();
        $this->assertSame('Theirs', $theirProfile->fresh()->name);

        // Submitting another room's profile id on my room creates a new profile instead of touching theirs.
        $mine = $this->room($owner);
        $this->actingAs($owner, 'owner')->put("/workspaces/{$mine->workspace_id}/rooms/{$mine->id}", $this->roomPayload([['id' => $theirProfile->id, 'name' => 'Mine', 'price_per_hour' => 5]]))->assertRedirect();
        $this->assertSame('Theirs', $theirProfile->fresh()->name);
        $this->assertSame(['Mine'], $mine->pricingProfiles()->pluck('name')->all());

        auth('owner')->logout();
        $role = Role::whereNull('owner_id')->where('key', 'receptionist')->firstOrFail();
        $staff = Staff::create(['owner_id' => $owner->id, 'role_id' => $role->id, 'name' => 'R', 'email' => 'r'.uniqid().'@t.local', 'password' => 'secret123', 'is_active' => true]);
        $staff->syncPermissionsFromRole();
        $this->actingAs($staff, 'staff')->put("/workspaces/{$mine->workspace_id}/rooms/{$mine->id}", $this->roomPayload([['name' => 'Staff', 'price_per_hour' => 1]]));
        $this->assertSame(['Mine'], $mine->pricingProfiles()->pluck('name')->all(), 'No workspaces.manage → unchanged.');
    }
}
