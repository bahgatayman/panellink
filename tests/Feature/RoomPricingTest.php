<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\SharedSession;
use App\Models\WorkingHour;
use App\Models\Workspace;
use App\Services\AnalyticsPeriod;
use App\Services\BookingService;
use App\Services\RevenueAnalyticsService;
use App\Services\RoomPricingService;
use App\Support\Pricing\PricingRules;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flexible room pricing: Room + people + duration → price, through the one
 * RoomPricingService, for bookings, quotes, shared sessions and checkout —
 * with every pre-existing (hourly) room priced exactly as before.
 */
class RoomPricingTest extends TestCase
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
            'name' => 'Test', 'slug' => 'test-'.uniqid(), 'max_members' => 100,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1,
            'features' => ['workspace', 'booking', 'sales'],
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

    private function room(Owner $owner, array $attrs = []): Room
    {
        $ws = Workspace::firstOrCreate(['owner_id' => $owner->id, 'name' => 'Main']);

        return Room::create(array_merge([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Room '.uniqid(),
            'type' => 'meeting', 'capacity' => 6, 'price_per_hour' => 100,
        ], $attrs));
    }

    /** A room priced by owner-defined rules, validated exactly as the room form would. */
    private function ruledRoom(Owner $owner, string $model, array $rules, array $attrs = []): Room
    {
        [$parsed, $errors] = PricingRules::fromInput($model, $rules);
        $this->assertSame([], $errors);

        return $this->room($owner, array_merge(['pricing_model' => $model, 'pricing_rules' => $parsed->toArray()], $attrs));
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Cust '.uniqid(), 'phone' => '010'.rand(10000000, 99999999), 'password' => 'pass1234',
        ]);
    }

    private function openHours(Owner $owner, string $open = '10:00:00', string $close = '22:00:00'): void
    {
        foreach (range(0, 6) as $day) {
            WorkingHour::create(['owner_id' => $owner->id, 'day_of_week' => $day, 'is_open' => true, 'open_time' => $open, 'close_time' => $close]);
        }
    }

    private function pricing(): RoomPricingService
    {
        return app(RoomPricingService::class);
    }

    private function quote(Room $room, int $people, string $start, string $end): float
    {
        return $this->pricing()->quoteBooking($room->fresh(), $people, today()->addDay()->toDateString(), $start, $end)->totalPrice;
    }

    private const DURATION_RULES = [
        'durations' => [['minutes' => 60], ['minutes' => 120], ['minutes' => 180], ['minutes' => 300], ['full_day' => true]],
        'prices' => [[100, 180, 250, 350, 500]],
    ];

    private const MATRIX_RULES = [
        'durations' => [['minutes' => 60], ['minutes' => 120], ['minutes' => 180], ['full_day' => true]],
        'people' => [['max' => 1], ['max' => 2], ['max' => 3]],
        'prices' => [[100, 180, 250, 500], [150, 270, 375, 700], [200, 360, 500, 900]],
    ];

    // --------------------------------------------------- 1. hourly (+ 6. legacy)

    public function test_hourly_room_is_hours_times_rate_exactly_like_booking_service(): void
    {
        $room = $this->room($this->owner(), ['price_per_hour' => 120]);

        $this->assertSame('hourly', $room->fresh()->pricing_model);
        foreach ([['10:00', '11:00'], ['10:00', '11:30'], ['09:00', '13:15']] as [$s, $e]) {
            $legacy = (new BookingService)->calculateBooking($s, $e, 120)['total_price'];
            $this->assertSame($legacy, $this->quote($room, 4, $s, $e), "{$s}–{$e}");
        }
        // People never change an hourly price.
        $this->assertSame($this->quote($room, 1, '10:00', '12:00'), $this->quote($room, 9, '10:00', '12:00'));
    }

    public function test_legacy_room_created_without_pricing_fields_books_at_its_hourly_rate(): void
    {
        $owner = $this->owner();
        // Exactly how rooms were created before flexible pricing existed.
        $room = $this->room($owner, ['price_per_hour' => 75]);
        $member = $this->member($owner);
        $date = today()->addDay()->toDateString();

        $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $member->id, 'booking_date' => $date,
            'start_time' => '10:00', 'end_time' => '12:30',
        ])->assertRedirect();

        $booking = Booking::where('room_id', $room->id)->firstOrFail();
        $this->assertSame('187.50', (string) $booking->total_price);
        $this->assertSame('75.00', (string) $booking->price_per_hour);
        $this->assertSame('2.50', (string) $booking->total_hours);
        $this->assertNull($booking->pricing_note);
        $this->assertSame(1, $booking->party_size);
    }

    // ---------------------------------------------------------- 2. duration-based

    public function test_duration_pricing_charges_the_next_package_up(): void
    {
        $room = $this->ruledRoom($this->owner(), 'duration', self::DURATION_RULES);

        $this->assertSame(100.0, $this->quote($room, 1, '10:00', '11:00'));  // exactly 1h
        $this->assertSame(180.0, $this->quote($room, 1, '10:00', '11:30'));  // 1.5h → 2h package
        $this->assertSame(250.0, $this->quote($room, 1, '10:00', '13:00'));  // 3h
        $this->assertSame(350.0, $this->quote($room, 1, '10:00', '14:00'));  // 4h → 5h package
        $this->assertSame(500.0, $this->quote($room, 1, '10:00', '16:00'));  // 6h → Full Day
        $this->assertSame(100.0, $this->quote($room, 5, '10:00', '11:00'));  // people don't matter here
    }

    public function test_duration_pricing_without_full_day_extends_the_longest_package_pro_rata(): void
    {
        $room = $this->ruledRoom($this->owner(), 'duration', [
            'durations' => [['minutes' => 60], ['minutes' => 300]],
            'prices' => [[100, 350]],
        ]);

        // 6h = the 5h package (350) + 1h at 350/5h = 70.
        $quote = $this->pricing()->quoteBooking($room->fresh(), 1, today()->addDay()->toDateString(), '10:00', '16:00');
        $this->assertSame(420.0, $quote->totalPrice);
        $this->assertStringContainsString('5 hours', $quote->note);
    }

    public function test_the_cheapest_covering_option_wins(): void
    {
        // An owner can make Full Day cheaper than a long package; the customer gets the better price.
        $room = $this->ruledRoom($this->owner(), 'duration', [
            'durations' => [['minutes' => 480], ['full_day' => true]],
            'prices' => [[450, 400]],
        ]);

        $this->assertSame(400.0, $this->quote($room, 1, '10:00', '12:00'));
    }

    // ------------------------------------------------------------ 3. people-based

    public function test_people_pricing_uses_the_tier_hourly_rate(): void
    {
        $room = $this->ruledRoom($this->owner(), 'people', [
            'people' => [['max' => 2], ['max' => 4], ['max' => 6]],
            'prices' => [[200], [300], [400]],
        ]);

        $this->assertSame(200.0, $this->quote($room, 1, '10:00', '11:00'));
        $this->assertSame(400.0, $this->quote($room, 2, '10:00', '12:00'));
        $this->assertSame(600.0, $this->quote($room, 3, '10:00', '12:00'));
        $this->assertSame(600.0, $this->quote($room, 5, '10:00', '11:30'));
        // Bigger than every tier → the last tier, never unpriceable.
        $this->assertSame(400.0, $this->quote($room, 9, '10:00', '11:00'));
    }

    // -------------------------------------------------------- 4. people + duration

    public function test_people_and_duration_matrix(): void
    {
        $room = $this->ruledRoom($this->owner(), 'people_duration', self::MATRIX_RULES);

        $this->assertSame(100.0, $this->quote($room, 1, '10:00', '11:00'));
        $this->assertSame(270.0, $this->quote($room, 2, '10:00', '12:00'));
        $this->assertSame(500.0, $this->quote($room, 3, '10:00', '13:00'));
        $this->assertSame(375.0, $this->quote($room, 2, '10:00', '12:30'));   // 2.5h → 3h column
        $this->assertSame(900.0, $this->quote($room, 3, '10:00', '15:00'));   // 5h → Full Day column

        $quote = $this->pricing()->quoteBooking($room->fresh(), 2, today()->addDay()->toDateString(), '10:00', '12:00');
        $this->assertSame('2 hours · 2 people', $quote->note);
    }

    public function test_rules_are_normalised_and_prices_follow_their_options(): void
    {
        // Submitted out of order: the grid must be re-ordered together with its rows/columns.
        [$rules, $errors] = PricingRules::fromInput('people_duration', [
            'durations' => [['full_day' => true], ['minutes' => 60]],
            'people' => [['max' => 4], ['max' => 2]],
            'prices' => [[900, 300], [700, 200]],
        ]);

        $this->assertSame([], $errors);
        $this->assertSame([['minutes' => 60], ['full_day' => true]], $rules->durations);
        $this->assertSame([['max' => 2], ['max' => 4]], $rules->people);
        $this->assertSame([[200.0, 700.0], [300.0, 900.0]], $rules->prices);
    }

    public function test_invalid_rules_are_rejected_with_owner_friendly_errors(): void
    {
        [, $errors] = PricingRules::fromInput('duration', ['durations' => [], 'prices' => []]);
        $this->assertContains(__('app.pricing.errors.duration_required'), $errors);

        [, $errors] = PricingRules::fromInput('people_duration', [
            'durations' => [['minutes' => 60]], 'people' => [['max' => 2]], 'prices' => [['']],
        ]);
        $this->assertContains(__('app.pricing.errors.price_missing'), $errors);

        [, $errors] = PricingRules::fromInput('duration', ['durations' => [['minutes' => 60], ['minutes' => 60]], 'prices' => [[1, 2]]]);
        $this->assertContains(__('app.pricing.errors.duration_duplicate'), $errors);
    }

    // --------------------------------------------------------------- 5. full day

    public function test_full_day_follows_business_hours(): void
    {
        $owner = $this->owner();
        $this->openHours($owner, '10:00:00', '22:00:00');
        $room = $this->ruledRoom($owner, 'duration', [
            'durations' => [['minutes' => 60], ['minutes' => 600], ['full_day' => true]],
            'prices' => [[100, 400, 500]],
        ]);
        $date = today()->addDay()->toDateString();

        $this->assertSame(720, $this->pricing()->fullDayMinutes($owner, $date));
        // 11h: longer than the 10h package → the Full Day (12h business day) price.
        $this->assertSame(500.0, $this->pricing()->quoteBooking($room->fresh(), 1, $date, '10:00', '21:00')->totalPrice);
        // The whole business day is exactly Full Day.
        $full = $this->pricing()->quoteBooking($room->fresh(), 1, $date, '10:00', '22:00');
        $this->assertSame(500.0, $full->totalPrice);
        $this->assertSame('Full day', $full->note);

        // The booking form's one-tap "Full day" gets that window from room-options.
        $this->actingAs($owner, 'owner')
            ->getJson('/bookings/room-options?'.http_build_query(['booking_date' => $date]))
            ->assertOk()
            ->assertJson(['rooms' => [], 'full_day' => ['start' => '10:00', 'end' => '22:00']]);
    }

    public function test_full_day_is_the_whole_day_when_hours_are_not_configured(): void
    {
        $owner = $this->owner();
        $this->assertSame(1440, $this->pricing()->fullDayMinutes($owner, today()->toDateString()));

        $this->actingAs($owner, 'owner')
            ->getJson('/bookings/room-options?'.http_build_query(['booking_date' => today()->addDay()->toDateString()]))
            ->assertOk()->assertJson(['full_day' => null]);
    }

    // ------------------------------------------------------ 8. booking calculation

    public function test_booking_store_prices_by_people_and_duration_and_keeps_availability_seats_at_one(): void
    {
        $owner = $this->owner();
        $room = $this->ruledRoom($owner, 'people_duration', self::MATRIX_RULES);
        $member = $this->member($owner);
        $date = today()->addDay()->toDateString();

        $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $member->id, 'booking_date' => $date,
            'start_time' => '10:00', 'end_time' => '12:00', 'guest_count' => 2, 'amount_paid' => 100,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $booking = Booking::where('room_id', $room->id)->firstOrFail();
        $this->assertSame('270.00', (string) $booking->total_price);
        $this->assertSame(2, $booking->guest_count);
        $this->assertSame(1, $booking->party_size, 'An exclusive room still consumes exactly one booking slot.');
        $this->assertSame('2 hours · 2 people', $booking->pricing_note);
        $this->assertSame('partial', $booking->payment_status);

        // Editing re-prices with the same service: 3 people, 3 hours.
        $this->actingAs($owner, 'owner')->put("/bookings/{$booking->id}", [
            'room_id' => $room->id, 'hotspot_user_id' => $member->id, 'booking_date' => $date,
            'start_time' => '10:00', 'end_time' => '13:00', 'guest_count' => 3,
        ])->assertRedirect();
        $this->assertSame('500.00', (string) $booking->fresh()->total_price);
        $this->assertSame(3, $booking->fresh()->guest_count);
    }

    public function test_deposit_cannot_exceed_the_rule_based_total(): void
    {
        $owner = $this->owner();
        $room = $this->ruledRoom($owner, 'duration', self::DURATION_RULES);

        $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'booking_date' => today()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00',
            'amount_paid' => 150,
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_room_quotes_use_the_same_pricing_for_every_room(): void
    {
        $owner = $this->owner();
        $hourly = $this->room($owner, ['name' => 'Hourly', 'price_per_hour' => 90]);
        $matrix = $this->ruledRoom($owner, 'people_duration', self::MATRIX_RULES, ['name' => 'Matrix']);
        $date = today()->addDay()->toDateString();

        $rooms = collect($this->actingAs($owner, 'owner')
            ->getJson('/bookings/room-options?'.http_build_query(['booking_date' => $date, 'start_time' => '10:00', 'end_time' => '12:00', 'guest_count' => 3]))
            ->assertOk()->json('rooms'))->keyBy('id');

        $this->assertEquals(180, $rooms[$hourly->id]['total_price']);
        $this->assertNull($rooms[$hourly->id]['price_note']);
        $this->assertEquals(360, $rooms[$matrix->id]['total_price']);
        $this->assertSame('2 hours · 3 people', $rooms[$matrix->id]['price_note']);

        // The single-room availability lookup agrees, and a group doesn't make an exclusive room "full".
        $this->actingAs($owner, 'owner')
            ->getJson('/bookings/check-availability?'.http_build_query([
                'room_id' => $matrix->id, 'booking_date' => $date, 'start_time' => '10:00', 'end_time' => '12:00', 'party_size' => 3,
            ]))
            ->assertOk()->assertJson(['available' => true, 'total_price' => 360]);
    }

    // ---------------------------------------------------- 7. shared-session pricing

    public function test_legacy_shared_session_billing_is_unchanged(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner, ['type' => 'shared', 'capacity' => 10, 'price_per_hour' => 60, 'billing_unit' => 'half_hour']);
        Carbon::setTestNow(Carbon::parse('2026-09-28 12:00:00'));

        $session = SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 1, 'session_date' => '2026-09-28', 'start_time' => '11:20',
            'opened_at' => Carbon::parse('2026-09-28 11:20:00'), 'status' => 'open',
            'billing_unit' => 'half_hour', 'billed_price_per_hour' => 60,
        ]);

        // 40 min on half-hour blocks → 2 blocks = 1h × 60.
        $this->actingAs($owner, 'owner')->getJson("/shared-sessions/{$session->id}/close-preview")
            ->assertOk()->assertJson(['total_price' => '60.00', 'pricing_note' => null]);
    }

    public function test_shared_session_uses_duration_packages_snapshotted_at_open(): void
    {
        $owner = $this->owner();
        $room = $this->ruledRoom($owner, 'duration', [
            'durations' => [['minutes' => 60], ['minutes' => 180], ['full_day' => true]],
            'prices' => [[50, 120, 200]],
        ], ['type' => 'shared', 'capacity' => 10]);
        $member = $this->member($owner);

        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00:00'));
        $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $member->id, 'session_date' => '2026-09-28', 'start_time' => '10:00',
        ])->assertRedirect();
        $session = SharedSession::where('room_id', $room->id)->firstOrFail();
        $this->assertSame('duration', $session->pricing_snapshot['model']);

        // The owner changes prices while the session is open — it must not affect this session.
        $room->update(['pricing_rules' => ['durations' => [['minutes' => 60], ['minutes' => 180], ['full_day' => true]], 'prices' => [[999, 999, 999]]]]);

        Carbon::setTestNow(Carbon::parse('2026-09-28 11:10:00')); // 70 min → the 3h package
        $this->actingAs($owner, 'owner')->getJson("/shared-sessions/{$session->id}/close-preview")
            ->assertOk()->assertJson(['total_price' => '120.00', 'pricing_note' => '3 hours']);

        // ---- 9. checkout: close charges exactly the previewed amount and records it as paid revenue.
        $this->actingAs($owner, 'owner')->postJson("/shared-sessions/{$session->id}/close")->assertOk()->assertJson(['success' => true]);

        $booking = Booking::where('room_id', $room->id)->where('status', 'completed')->firstOrFail();
        $this->assertSame('120.00', (string) $booking->total_price);
        $this->assertSame('120.00', (string) $booking->amount_paid);
        $this->assertSame('3 hours', $booking->pricing_note);
        $this->assertSame('120.00', (string) $session->fresh()->total_price);

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-09-28')->startOfDay(), Carbon::parse('2026-09-28')->endOfDay());
        $this->assertSame(120.0, app(RevenueAnalyticsService::class)->bookingRevenue($owner, $period));
    }

    public function test_shared_session_people_tiers_use_the_party_size(): void
    {
        $owner = $this->owner();
        $room = $this->ruledRoom($owner, 'people', [
            'people' => [['max' => 1], ['max' => 4]],
            'prices' => [[40], [100]],
        ], ['type' => 'shared', 'capacity' => 10]);

        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00:00'));
        $this->actingAs($owner, 'owner')->post('/shared-sessions', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'session_date' => '2026-09-28', 'start_time' => '10:00', 'party_size' => 3,
        ])->assertRedirect();
        $session = SharedSession::where('room_id', $room->id)->firstOrFail();

        Carbon::setTestNow(Carbon::parse('2026-09-28 11:30:00'));
        $this->actingAs($owner, 'owner')->getJson("/shared-sessions/{$session->id}/close-preview")
            ->assertOk()->assertJson(['total_price' => '150.00']);

        // The Active Sessions card shows the same running estimate and ticks at the tier rate.
        $this->actingAs($owner, 'owner')->get('/active-sessions')
            ->assertOk()->assertSee('EGP 150.00', false)->assertSee('data-ls-rate="100"', false);
    }

    public function test_checked_in_reservation_freezes_rules_onto_its_session(): void
    {
        $owner = $this->owner();
        $room = $this->ruledRoom($owner, 'duration', [
            'durations' => [['minutes' => 120]], 'prices' => [[80]],
        ], ['type' => 'shared', 'capacity' => 10]);
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00:00'));

        $booking = Booking::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 1, 'booking_date' => '2026-09-28', 'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 40, 'total_hours' => 2, 'total_price' => 80, 'status' => 'confirmed',
        ]);

        $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/check-in", ['party_size' => 1])->assertRedirect();

        $session = SharedSession::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame(['model' => 'duration', 'rules' => $room->fresh()->pricing_rules], $session->pricing_snapshot);
    }

    // ------------------------------------------------ 9. exclusive checkout

    public function test_exclusive_checkout_uses_the_stored_rule_based_total(): void
    {
        $owner = $this->owner();
        $room = $this->ruledRoom($owner, 'people_duration', self::MATRIX_RULES);
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:30:00'));

        $this->actingAs($owner, 'owner')->post('/bookings', [
            'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner)->id, 'booking_date' => '2026-09-28',
            'start_time' => '10:00', 'end_time' => '13:00', 'guest_count' => 3,
        ])->assertRedirect();

        // The in-progress booking's card on Active Sessions checks out for the quoted 500.
        $this->actingAs($owner, 'owner')->get('/active-sessions')
            ->assertOk()->assertSee('EGP 500.00', false);
    }

    // ---------------------------------------------------------- owner room form

    public function test_owner_can_create_and_edit_a_room_with_a_pricing_matrix(): void
    {
        $owner = $this->owner();
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'HQ']);

        $this->actingAs($owner, 'owner')->get(route('rooms.create', $ws))
            ->assertOk()->assertSee(__('app.pricing.question'))->assertSee('name="pricing_model"', false);

        $this->actingAs($owner, 'owner')->post(route('rooms.store', $ws), [
            'name' => 'Board Room', 'type' => 'meeting', 'capacity' => 6,
            'pricing_model' => 'people_duration',
            'pricing_rules' => json_encode(self::MATRIX_RULES),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $room = Room::where('name', 'Board Room')->firstOrFail();
        $this->assertSame('people_duration', $room->pricing_model);
        $this->assertSame(270.0, $this->quote($room, 2, '10:00', '12:00'));

        // Back to hourly: the rules are dropped and the hourly rate applies again.
        $this->actingAs($owner, 'owner')->put(route('rooms.update', [$ws, $room]), [
            'name' => 'Board Room', 'type' => 'meeting', 'capacity' => 6,
            'pricing_model' => 'hourly', 'price_per_hour' => 150,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $room->refresh();
        $this->assertSame('hourly', $room->pricing_model);
        $this->assertNull($room->pricing_rules);
        $this->assertSame(300.0, $this->quote($room, 2, '10:00', '12:00'));
    }

    public function test_room_form_rejects_incomplete_pricing(): void
    {
        $owner = $this->owner();
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'HQ']);

        $this->actingAs($owner, 'owner')->post(route('rooms.store', $ws), [
            'name' => 'Pod', 'type' => 'meeting', 'capacity' => 2,
            'pricing_model' => 'duration',
            'pricing_rules' => json_encode(['durations' => [['minutes' => 60]], 'prices' => [['']]]),
        ])->assertSessionHasErrors('pricing_rules');

        // Hourly still requires its price, exactly as before.
        $this->actingAs($owner, 'owner')->post(route('rooms.store', $ws), [
            'name' => 'Pod', 'type' => 'meeting', 'capacity' => 2,
        ])->assertSessionHasErrors('price_per_hour');

        $this->assertDatabaseCount('rooms', 0);
    }
}
