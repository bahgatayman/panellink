<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\SharedSession;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 0 of the billing-policy work: close() must compute money server-side
 * and must not let two concurrent requests both close the same session.
 */
class SharedSessionClosingTest extends TestCase
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

    /** An open session, back-dated 10 minutes, on a room priced at 60/hr, billed per minute. */
    private function openSession(Owner $owner, Carbon $openedAt): SharedSession
    {
        return $this->openSessionWithBilling($owner, $openedAt, 'minute', 60);
    }

    /** An open session with an explicit billing_unit/price, snapshotted onto the session as store() would. */
    private function openSessionWithBilling(Owner $owner, Carbon $openedAt, string $billingUnit, float $pricePerHour): SharedSession
    {
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);
        $room = Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Lounge',
            'type' => 'shared', 'capacity' => 8, 'price_per_hour' => $pricePerHour, 'billing_unit' => $billingUnit,
        ]);
        $user = HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Cust', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);

        return SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'session_date' => $openedAt->toDateString(), 'start_time' => $openedAt->format('H:i'),
            'opened_at' => $openedAt, 'status' => 'open',
            'billing_unit' => $billingUnit, 'billed_price_per_hour' => $pricePerHour,
        ]);
    }

    public function test_close_computes_total_server_side_ignoring_client_submitted_values(): void
    {
        $owner = $this->owner();
        $now = Carbon::parse('2026-08-01 12:00:00');
        $session = $this->openSession($owner, $now->copy()->subMinutes(10));

        Carbon::setTestNow($now);

        // A tampered/stale payload — none of this should reach the database.
        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close", [
                'closed_at' => '2099-01-01 00:00:00',
                'total_minutes' => 999999,
                'total_price' => 999999,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 10 minutes @ 60/hr = 10.00.
        $session->refresh();
        $this->assertEquals(10.0, (float) $session->total_minutes);
        $this->assertEquals(10.0, (float) $session->total_price);
        $this->assertSame('closed', $session->status);

        $booking = Booking::where('id', $session->booking_id)->firstOrFail();
        $this->assertEquals(10.0, (float) $booking->total_price);
    }

    public function test_double_close_is_rejected_and_does_not_double_book(): void
    {
        $owner = $this->owner();
        $now = Carbon::parse('2026-08-01 12:00:00');
        $session = $this->openSession($owner, $now->copy()->subMinutes(5));
        Carbon::setTestNow($now);

        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close")
            ->assertStatus(409)
            ->assertJson(['success' => false]);

        $this->assertSame(1, Booking::where('owner_id', $owner->id)->count());
        $this->assertSame('closed', $session->fresh()->status);
    }

    /**
     * @return array<string, array{0: int, 1: string, 2: float}>
     */
    public static function billingExamplesProvider(): array
    {
        return [
            '7 min, per minute' => [7, 'minute', 7.00],
            '7 min, per 30 min' => [7, 'half_hour', 30.00],
            '7 min, per hour' => [7, 'hour', 60.00],
            '30 min, per minute' => [30, 'minute', 30.00],
            '30 min, per 30 min (exact boundary)' => [30, 'half_hour', 30.00],
            '30 min, per hour' => [30, 'hour', 60.00],
            '31 min, per minute' => [31, 'minute', 31.00],
            '31 min, per 30 min' => [31, 'half_hour', 60.00],
            '31 min, per hour' => [31, 'hour', 60.00],
            '59 min, per minute' => [59, 'minute', 59.00],
            '59 min, per 30 min' => [59, 'half_hour', 60.00],
            '59 min, per hour' => [59, 'hour', 60.00],
            '60 min, per minute' => [60, 'minute', 60.00],
            '60 min, per 30 min (exact boundary)' => [60, 'half_hour', 60.00],
            '60 min, per hour (exact boundary)' => [60, 'hour', 60.00],
            '61 min, per minute' => [61, 'minute', 61.00],
            '61 min, per 30 min' => [61, 'half_hour', 90.00],
            '61 min, per hour' => [61, 'hour', 120.00],
        ];
    }

    #[DataProvider('billingExamplesProvider')]
    public function test_block_billing_matches_the_documented_examples(int $minutes, string $billingUnit, float $expectedTotal): void
    {
        $owner = $this->owner();
        $now = Carbon::parse('2026-08-01 12:00:00');
        $session = $this->openSessionWithBilling($owner, $now->copy()->subMinutes($minutes), $billingUnit, 60);
        Carbon::setTestNow($now);

        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals($expectedTotal, (float) $session->fresh()->total_price);
    }

    public function test_changing_the_rooms_billing_unit_mid_session_does_not_affect_an_already_open_session(): void
    {
        $owner = $this->owner();
        $now = Carbon::parse('2026-08-01 12:00:00');
        // Opened while the room billed per minute (7 minutes @ 60/hr = 7.00).
        $session = $this->openSessionWithBilling($owner, $now->copy()->subMinutes(7), 'minute', 60);

        // Owner switches the room to hourly billing while the session is still open.
        $session->room->update(['billing_unit' => 'hour']);

        Carbon::setTestNow($now);
        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close")
            ->assertOk();

        // Still billed under the rule that was in effect when it opened, not the new one.
        $this->assertEquals(7.00, (float) $session->fresh()->total_price);
    }

    public function test_changing_the_rooms_price_mid_session_does_not_affect_an_already_open_session(): void
    {
        $owner = $this->owner();
        $now = Carbon::parse('2026-08-01 12:00:00');
        // Opened at 60/hr (10 minutes = 10.00).
        $session = $this->openSessionWithBilling($owner, $now->copy()->subMinutes(10), 'minute', 60);

        // Owner raises the room's rate while the session is still open.
        $session->room->update(['price_per_hour' => 120]);

        Carbon::setTestNow($now);
        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close")
            ->assertOk();

        // Still billed at the rate snapshotted when it opened, not the new one.
        $this->assertEquals(10.00, (float) $session->fresh()->total_price);
    }
}
