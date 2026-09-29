<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\RoomPlan;
use App\Models\SharedSession;
use App\Models\WorkingHour;
use App\Models\Workspace;
use App\Services\AnalyticsPeriod;
use App\Services\RevenueAnalyticsService;
use App\Services\RoomPricingService;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Custom Plans: fixed-price packages (exactly N people, a set duration) that
 * sit beside a room's normal pricing and are priced by RoomPricingService.
 */
class RoomPlansTest extends TestCase
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
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Meeting Room',
            'type' => 'meeting', 'capacity' => 20, 'price_per_hour' => 100,
        ], $attrs));
    }

    /** The three example plans from the brief. */
    private function examplePlans(Room $room): array
    {
        $mk = fn ($name, $people, $minutes, $fullDay, $price, $i) => RoomPlan::create([
            'owner_id' => $room->owner_id, 'room_id' => $room->id, 'name' => $name, 'people' => $people,
            'duration_minutes' => $minutes, 'is_full_day' => $fullDay, 'price' => $price, 'sort_order' => $i,
        ]);

        return [
            'team' => $mk('Team Package', 10, 300, false, 500, 0),
            'small' => $mk('Small Meeting', 6, 180, false, 350, 1),
            'day' => $mk('Full Day Team', 15, null, true, 1000, 2),
        ];
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create(['owner_id' => $owner->id, 'name' => 'Hana Tarek', 'phone' => '010'.rand(10000000, 99999999), 'password' => 'pass1234']);
    }

    private function book(Owner $owner, Room $room, array $extra)
    {
        return $this->actingAs($owner, 'owner')->post('/bookings', array_merge([
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'booking_date' => today()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00',
        ], $extra));
    }

    // ------------------------------------------------ 1. room form: create/edit/delete

    public function test_owner_creates_edits_and_deletes_plans_through_the_room_form(): void
    {
        $owner = $this->owner();
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'HQ']);

        $this->actingAs($owner, 'owner')->get(route('rooms.create', $ws))->assertOk()->assertSee(__('app.plans.section'));

        $this->actingAs($owner, 'owner')->post(route('rooms.store', $ws), [
            'name' => 'Board Room', 'type' => 'meeting', 'capacity' => 20, 'price_per_hour' => 100,
            'plans' => json_encode([
                ['name' => 'Team Package', 'people' => 10, 'minutes' => 300, 'price' => 500],
                ['name' => '', 'people' => 15, 'full_day' => true, 'price' => 1000],
            ]),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $room = Room::where('name', 'Board Room')->firstOrFail();
        $plans = $room->plans()->get();
        $this->assertCount(2, $plans);
        $this->assertSame('Team Package', $plans[0]->name);
        $this->assertTrue($plans[1]->is_full_day);
        $this->assertSame('hourly', $room->pricing_model, 'Plans never replace the room\'s own pricing.');

        // Edit: change the first, drop the second, add a third.
        $this->actingAs($owner, 'owner')->put(route('rooms.update', [$ws, $room]), [
            'name' => 'Board Room', 'type' => 'meeting', 'capacity' => 20, 'price_per_hour' => 100,
            'plans' => json_encode([
                ['id' => $plans[0]->id, 'name' => 'Team Package', 'people' => 10, 'minutes' => 300, 'price' => 550],
                ['name' => 'Small Meeting', 'people' => 6, 'minutes' => 180, 'price' => 350],
            ]),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fresh = $room->plans()->get();
        $this->assertCount(2, $fresh);
        $this->assertSame($plans[0]->id, $fresh[0]->id);
        $this->assertSame('550.00', (string) $fresh[0]->price);
        $this->assertDatabaseMissing('room_plans', ['id' => $plans[1]->id]);

        // A save without the plans field (older client) leaves plans untouched.
        $this->actingAs($owner, 'owner')->put(route('rooms.update', [$ws, $room]), [
            'name' => 'Board Room', 'type' => 'meeting', 'capacity' => 20, 'price_per_hour' => 100,
        ])->assertRedirect();
        $this->assertCount(2, $room->plans()->get());
    }

    public function test_room_form_rejects_invalid_plans(): void
    {
        $owner = $this->owner();
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'HQ']);
        $post = fn (array $plans, array $room = []) => $this->actingAs($owner, 'owner')->post(route('rooms.store', $ws), array_merge([
            'name' => 'Pod', 'type' => 'meeting', 'capacity' => 8, 'price_per_hour' => 100, 'plans' => json_encode($plans),
        ], $room));

        $post([['people' => 4, 'minutes' => 60, 'price' => '']])->assertSessionHasErrors('plans');
        $post([['people' => 4, 'minutes' => 5, 'price' => 100]])->assertSessionHasErrors('plans');
        $post([['people' => 4, 'minutes' => 60, 'price' => 1], ['people' => 4, 'minutes' => 60, 'price' => 2]])->assertSessionHasErrors('plans');
        // Shared rooms: a plan can't seat more than the room holds.
        $post([['people' => 30, 'minutes' => 60, 'price' => 1]], ['type' => 'shared', 'capacity' => 10])->assertSessionHasErrors('plans');

        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_plan_ids_from_another_room_are_never_updated(): void
    {
        $owner = $this->owner();
        $other = $this->room($this->owner(), ['name' => 'Elsewhere']);
        $foreign = $this->examplePlans($other)['team'];
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'HQ']);
        $room = $this->room($owner, ['workspace_id' => $ws->id]);

        $this->actingAs($owner, 'owner')->put(route('rooms.update', [$ws, $room]), [
            'name' => 'Meeting Room', 'type' => 'meeting', 'capacity' => 20, 'price_per_hour' => 100,
            'plans' => json_encode([['id' => $foreign->id, 'name' => 'Hijack', 'people' => 2, 'minutes' => 60, 'price' => 1]]),
        ])->assertRedirect();

        $this->assertSame('Team Package', $foreign->fresh()->name, 'Another owner\'s plan is untouched.');
        $this->assertSame('Hijack', $room->plans()->first()->name, 'The row became a new plan on this room instead.');
    }

    // ------------------------------------------------------------ 2. pricing service

    public function test_quote_plan_is_the_fixed_price_for_exactly_its_people(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        ['team' => $team] = $this->examplePlans($room);
        $pricing = app(RoomPricingService::class);
        $date = today()->addDay()->toDateString();

        $quote = $pricing->quotePlan($room, $team, 10, $date, '10:00');
        $this->assertSame(500.0, $quote->totalPrice);
        $this->assertSame(300.0, $quote->totalMinutes);
        $this->assertSame('Team Package · 10 people · 5 hours', $quote->note);
        $this->assertSame(['start' => '10:00', 'end' => '15:00', 'minutes' => 300], $pricing->planWindow($room, $team, $date, '10:00'));

        $this->expectException(\InvalidArgumentException::class);
        $pricing->quotePlan($room, $team, 9, $date, '10:00');
    }

    public function test_full_day_plan_follows_working_hours(): void
    {
        $owner = $this->owner();
        foreach (range(0, 6) as $d) {
            WorkingHour::create(['owner_id' => $owner->id, 'day_of_week' => $d, 'is_open' => true, 'open_time' => '10:00:00', 'close_time' => '22:00:00']);
        }
        $room = $this->room($owner);
        ['day' => $day] = $this->examplePlans($room);

        $window = app(RoomPricingService::class)->planWindow($room, $day, today()->addDay()->toDateString(), '14:00');
        $this->assertSame(['start' => '10:00', 'end' => '22:00', 'minutes' => 720], $window);
    }

    // ---------------------------------------------------- 3–5. booking create/edit

    public function test_booking_with_a_plan_uses_its_price_and_duration(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        ['team' => $team] = $this->examplePlans($room);

        // The submitted end is ignored: the plan decides the booking's length.
        $this->book($owner, $room, ['room_plan_id' => $team->id, 'guest_count' => 10, 'end_time' => '11:00', 'amount_paid' => 200])
            ->assertRedirect()->assertSessionHasNoErrors();

        $booking = Booking::firstOrFail();
        $this->assertSame($team->id, $booking->room_plan_id);
        $this->assertSame('500.00', (string) $booking->total_price);
        $this->assertSame('15:00', substr($booking->end_time, 0, 5));
        $this->assertSame('5.00', (string) $booking->total_hours);
        $this->assertSame('Team Package · 10 people · 5 hours', $booking->pricing_note);
        $this->assertSame('partial', $booking->payment_status);
    }

    public function test_plan_booking_rejects_wrong_people_deposit_over_total_and_foreign_plans(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        ['team' => $team] = $this->examplePlans($room);
        $otherPlan = $this->examplePlans($this->room($this->owner(), ['name' => 'X']))['team'];

        $this->book($owner, $room, ['room_plan_id' => $team->id, 'guest_count' => 8])->assertSessionHas('error');
        $this->book($owner, $room, ['room_plan_id' => $team->id, 'guest_count' => 10, 'amount_paid' => 600])->assertSessionHas('error');
        $this->book($owner, $room, ['room_plan_id' => $otherPlan->id, 'guest_count' => 10])->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_plan_booking_respects_availability_and_working_hours_for_its_own_window(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        ['team' => $team] = $this->examplePlans($room);

        // 14:00–15:00 is taken; a 10:00 standard 1h booking would fit, but the 5h plan (10–15) overlaps.
        $this->book($owner, $room, ['start_time' => '14:00', 'end_time' => '15:00'])->assertRedirect();
        $this->book($owner, $room, ['room_plan_id' => $team->id, 'guest_count' => 10])->assertSessionHas('error');
        $this->assertDatabaseCount('bookings', 1);

        foreach (range(0, 6) as $d) {
            WorkingHour::create(['owner_id' => $owner->id, 'day_of_week' => $d, 'is_open' => true, 'open_time' => '08:00:00', 'close_time' => '12:00:00']);
        }
        // 08:00 + 5h = 13:00, past closing.
        $this->book($owner, $room, ['room_plan_id' => $team->id, 'guest_count' => 10, 'start_time' => '08:00', 'end_time' => '09:00'])
            ->assertSessionHas('error', __('app.booking.outside_working_hours'));
    }

    public function test_editing_switches_between_plan_and_standard_pricing(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        ['small' => $small] = $this->examplePlans($room);
        $member = $this->member($owner);
        $date = today()->addDay()->toDateString();
        $base = ['room_id' => $room->id, 'hotspot_user_id' => $member->id, 'booking_date' => $date, 'start_time' => '10:00'];

        $this->actingAs($owner, 'owner')->post('/bookings', $base + ['end_time' => '12:00', 'guest_count' => 6])->assertRedirect();
        $booking = Booking::firstOrFail();
        $this->assertSame('200.00', (string) $booking->total_price);

        $this->actingAs($owner, 'owner')->put("/bookings/{$booking->id}", $base + ['room_plan_id' => $small->id, 'guest_count' => 6])->assertRedirect();
        $booking->refresh();
        $this->assertSame('350.00', (string) $booking->total_price);
        $this->assertSame($small->id, $booking->room_plan_id);
        $this->assertSame('13:00', substr($booking->end_time, 0, 5));

        $this->actingAs($owner, 'owner')->put("/bookings/{$booking->id}", $base + ['end_time' => '11:00', 'guest_count' => 6])->assertRedirect();
        $booking->refresh();
        $this->assertNull($booking->room_plan_id);
        $this->assertSame('100.00', (string) $booking->total_price);
    }

    // ------------------------------------------------------------ 6. room quotes

    public function test_room_options_list_plans_with_fit_and_availability(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $this->examplePlans($room);

        $plans = collect($this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query([
            'booking_date' => today()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00', 'guest_count' => 6,
        ]))->assertOk()->json('rooms'))->firstWhere('id', $room->id)['plans'];

        $this->assertCount(3, $plans);
        $small = collect($plans)->firstWhere('name', 'Small Meeting');
        $this->assertTrue($small['fits_people']);
        $this->assertSame('free', $small['state']);
        $this->assertSame('13:00', $small['end_time']);
        $this->assertEquals(350, $small['price']);
        $this->assertFalse(collect($plans)->firstWhere('name', 'Team Package')['fits_people']);
    }

    // -------------------------------------- 7. shared session: plan + overtime, revenue

    public function test_checked_in_plan_session_bills_plan_price_plus_overtime_at_standard_pricing(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, ['type' => 'shared', 'capacity' => 20, 'price_per_hour' => 60, 'billing_unit' => 'minute']);
        $plan = RoomPlan::create(['owner_id' => $owner->id, 'room_id' => $room->id, 'name' => 'Study Pack', 'people' => 2,
            'duration_minutes' => 120, 'is_full_day' => false, 'price' => 150, 'sort_order' => 0]);
        Carbon::setTestNow(Carbon::parse('2026-09-30 10:00:00'));

        $this->book($owner, $room, ['booking_date' => '2026-09-30', 'room_plan_id' => $plan->id, 'party_size' => 2])->assertRedirect();
        $booking = Booking::firstOrFail();
        $this->assertSame('150.00', (string) $booking->total_price);

        $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/check-in", ['party_size' => 2])->assertRedirect();
        $session = SharedSession::where('booking_id', $booking->id)->firstOrFail();
        $this->assertEquals(['note' => 'Study Pack · 2 people · 2 hours', 'minutes' => 120, 'price' => 150], $session->plan_snapshot);

        // Within the plan: just the plan price.
        Carbon::setTestNow(Carbon::parse('2026-09-30 11:30:00'));
        $this->actingAs($owner, 'owner')->getJson("/shared-sessions/{$session->id}/close-preview")
            ->assertOk()->assertJson(['total_price' => '150.00']);

        // 40 minutes over: + 40 min at 60/hr = 40.
        Carbon::setTestNow(Carbon::parse('2026-09-30 12:40:00'));
        $this->actingAs($owner, 'owner')->postJson("/shared-sessions/{$session->id}/close")->assertOk();

        $closed = Booking::where('room_id', $room->id)->where('status', 'completed')->latest('id')->firstOrFail();
        $this->assertSame('190.00', (string) $closed->total_price);
        $this->assertStringContainsString('Study Pack', $closed->pricing_note);
        $this->assertStringContainsString('40 min', $closed->pricing_note);

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-09-30')->startOfDay(), Carbon::parse('2026-09-30')->endOfDay());
        $this->assertSame(190.0, app(RevenueAnalyticsService::class)->bookingRevenue($owner, $period));
    }

    // ------------------------------------------------------ 8–9. history + no plans

    public function test_deleting_a_plan_keeps_past_booking_totals(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        ['team' => $team] = $this->examplePlans($room);
        $this->book($owner, $room, ['room_plan_id' => $team->id, 'guest_count' => 10])->assertRedirect();

        $team->delete();

        $booking = Booking::firstOrFail();
        $this->assertNull($booking->room_plan_id);
        $this->assertSame('500.00', (string) $booking->total_price);
        $this->assertSame('Team Package · 10 people · 5 hours', $booking->pricing_note);
    }

    public function test_rooms_without_plans_book_exactly_as_before(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, ['price_per_hour' => 75]);

        $this->book($owner, $room, ['end_time' => '12:30'])->assertRedirect();
        $booking = Booking::firstOrFail();
        $this->assertNull($booking->room_plan_id);
        $this->assertSame('187.50', (string) $booking->total_price);

        $rooms = $this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query([
            'booking_date' => today()->addDays(2)->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00',
        ]))->json('rooms');
        $this->assertSame([], collect($rooms)->firstWhere('id', $room->id)['plans']);
    }
}
