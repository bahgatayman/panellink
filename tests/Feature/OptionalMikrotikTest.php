<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Plan;
use App\Services\HotspotSyncService;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionalMikrotikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
        Plan::create([
            'name' => 'Basic', 'slug' => 'basic', 'max_members' => 100,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1,
        ]);
    }

    /** Create an owner with the given features enabled (and, by default, no router creds). */
    private function makeOwner(array $features = [], array $overrides = []): Owner
    {
        $owner = Owner::create(array_merge([
            'name' => 'Owner', 'email' => 'o' . uniqid() . '@t.local', 'password' => 'password',
            'business_name' => 'Space', 'plan_id' => Plan::first()->id, 'is_active' => true,
            'subscription_starts_at' => now(), 'subscription_expires_at' => now()->addMonth(),
        ], $overrides));

        foreach ($features as $f) {
            $owner->enableFeature($f);
        }

        return $owner->fresh();
    }

    public function test_booking_owner_can_reach_members_and_dashboard_but_not_hotspot_routes(): void
    {
        $owner = $this->makeOwner(['booking']);

        $this->actingAs($owner, 'owner')->get('/dashboard')->assertOk();
        $this->actingAs($owner, 'owner')->get('/users')->assertOk();
        // hotspot-only routes are denied (redirected to dashboard by CheckFeature)
        $this->actingAs($owner, 'owner')->get('/sessions')->assertRedirect('/dashboard');
        $this->actingAs($owner, 'owner')->get('/speed-profiles')->assertRedirect('/dashboard');
    }

    public function test_booking_owner_creates_member_without_router_or_profile(): void
    {
        $owner = $this->makeOwner(['booking']);

        $this->actingAs($owner, 'owner')
            ->post('/users', ['name' => 'Walk-in', 'phone' => '01000001'])
            ->assertRedirect('/users');

        $this->assertDatabaseHas('hotspot_users', [
            'owner_id' => $owner->id, 'phone' => '01000001', 'speed_profile_id' => null,
        ]);
    }

    public function test_owner_with_neither_feature_is_denied_member_registry(): void
    {
        $owner = $this->makeOwner(['workspace']); // any-of(hotspot,booking) => denied

        $this->actingAs($owner, 'owner')->get('/users')->assertRedirect('/dashboard');
    }

    public function test_sync_service_is_noop_for_non_hotspot_owner(): void
    {
        $owner = $this->makeOwner(['booking']);
        $sync = app(HotspotSyncService::class);

        $this->assertSame([], $sync->activeUsers($owner));
        // must not throw / must not attempt a socket connection
        $sync->createUser($owner, '01000002', '01000002', 'Default');
        $this->assertTrue(true);
    }

    public function test_hotspot_owner_without_router_creds_still_loads_sessions(): void
    {
        // hotspot enabled but no creds => shouldSync false => no socket opened, page renders
        $owner = $this->makeOwner(['hotspot']);

        $this->actingAs($owner, 'owner')->get('/sessions')->assertOk();
    }

    public function test_owner_creatable_without_router_credentials(): void
    {
        $owner = $this->makeOwner(['booking']);

        $this->assertNull($owner->mikrotik_host);
        $this->assertFalse($owner->hasRouterConfigured());
    }
}
