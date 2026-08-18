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

    /** An open session, back-dated 10 minutes, on a room priced at 60/hr. */
    private function openSession(Owner $owner, Carbon $openedAt): SharedSession
    {
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);
        $room = Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Lounge',
            'type' => 'shared', 'capacity' => 8, 'price_per_hour' => 60,
        ]);
        $user = HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Cust', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);

        return SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'session_date' => $openedAt->toDateString(), 'start_time' => $openedAt->format('H:i'),
            'opened_at' => $openedAt, 'status' => 'open',
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
}
