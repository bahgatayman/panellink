<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\Workspace;
use App\Support\Pricing\PricingRules;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** EXPERIMENT — the quick booking modal and the JSON answers it relies on. */
class QuickBookingModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
    }

    private function owner(array $features = ['workspace', 'booking']): Owner
    {
        $plan = Plan::create([
            'name' => 'Test', 'slug' => 'test-'.uniqid(), 'max_members' => 100, 'price_per_month' => 0,
            'is_active' => true, 'sort_order' => 1, 'features' => $features,
            'max_workspaces' => 0, 'max_rooms' => 0, 'max_products' => 0,
        ]);
        $owner = Owner::create([
            'name' => 'Owner', 'email' => 'o'.uniqid().'@t.local', 'password' => 'secret123', 'business_name' => 'Space',
            'plan_id' => $plan->id, 'is_active' => true, 'subscription_starts_at' => now(), 'subscription_expires_at' => now()->addMonth(),
        ]);
        foreach ($features as $key) {
            $owner->enableFeature($key);
        }

        return $owner;
    }

    private function room(Owner $owner, array $attrs = []): Room
    {
        $ws = Workspace::firstOrCreate(['owner_id' => $owner->id, 'name' => 'Main']);

        return Room::create(array_merge(['owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Room',
            'type' => 'meeting', 'capacity' => 6, 'price_per_hour' => 100], $attrs));
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create(['owner_id' => $owner->id, 'name' => 'Hana Tarek', 'phone' => '01220189954', 'password' => 'pass1234']);
    }

    private function payload(Room $room, HotspotUser $member, array $extra = []): array
    {
        return array_merge([
            'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'booking_date' => today()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '12:00',
        ], $extra);
    }

    public function test_modal_is_on_owner_pages_when_booking_is_enabled(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner, 'owner')->get('/dashboard')
            ->assertOk()->assertSee('id="quick-book"', false)->assertSee(__('app.quick_booking.title'));
    }

    public function test_modal_is_absent_without_the_booking_feature(): void
    {
        $owner = $this->owner(['workspace']);

        $this->actingAs($owner, 'owner')->get('/dashboard')->assertOk()->assertDontSee('id="quick-book"', false);
    }

    public function test_json_store_creates_the_booking_at_the_quoted_price(): void
    {
        $owner = $this->owner();
        $member = $this->member($owner);
        [$rules] = PricingRules::fromInput('people', ['people' => [['max' => 2], ['max' => 6]], 'prices' => [[100], [150]]]);
        $room = $this->room($owner, ['pricing_model' => 'people', 'pricing_rules' => $rules->toArray()]);

        $response = $this->actingAs($owner, 'owner')
            ->postJson('/bookings', $this->payload($room, $member, ['guest_count' => 4, 'amount_paid' => 100]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $booking = Booking::findOrFail($response->json('booking_id'));
        $response->assertJson(['url' => "/bookings/{$booking->id}"]);
        $this->assertStringContainsString('Hana Tarek', $response->json('message'));
        $this->assertSame('300.00', (string) $booking->total_price);
        $this->assertSame(4, $booking->guest_count);
        $this->assertSame('partial', $booking->payment_status);
    }

    public function test_json_store_reports_conflicts_and_rule_failures_as_json(): void
    {
        $owner = $this->owner();
        $member = $this->member($owner);
        $room = $this->room($owner);

        $this->actingAs($owner, 'owner')->postJson('/bookings', $this->payload($room, $member))->assertOk();

        // Same room, same time → a clear message, not a redirect.
        $this->actingAs($owner, 'owner')->postJson('/bookings', $this->payload($room, $member))
            ->assertStatus(422)->assertJson(['success' => false])->assertJsonStructure(['message']);

        // Deposit above the total.
        $this->actingAs($owner, 'owner')
            ->postJson('/bookings', $this->payload($room, $member, ['start_time' => '14:00', 'end_time' => '15:00', 'amount_paid' => 999]))
            ->assertStatus(422)->assertJson(['success' => false]);

        // Field validation keeps Laravel's standard JSON shape.
        $this->actingAs($owner, 'owner')->postJson('/bookings', ['room_id' => $room->id])
            ->assertStatus(422)->assertJson(['success' => false])->assertJsonStructure(['message', 'errors' => ['hotspot_user_id', 'booking_date']]);
    }

    public function test_full_page_form_post_still_redirects(): void
    {
        $owner = $this->owner();
        $booking = $this->actingAs($owner, 'owner')->post('/bookings', $this->payload($this->room($owner), $this->member($owner)));

        $booking->assertRedirect('/bookings/'.Booking::firstOrFail()->id);
    }

    public function test_room_options_say_which_rooms_price_by_people(): void
    {
        $owner = $this->owner();
        [$rules] = PricingRules::fromInput('people', ['people' => [['max' => 2]], 'prices' => [[100]]]);
        $byPeople = $this->room($owner, ['name' => 'A', 'pricing_model' => 'people', 'pricing_rules' => $rules->toArray()]);
        $hourly = $this->room($owner, ['name' => 'B']);

        $rooms = collect($this->actingAs($owner, 'owner')->getJson('/bookings/room-options?'.http_build_query([
            'booking_date' => today()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00',
        ]))->assertOk()->json('rooms'))->keyBy('id');

        $this->assertTrue($rooms[$byPeople->id]['uses_people']);
        $this->assertFalse($rooms[$hourly->id]['uses_people']);
    }
}
