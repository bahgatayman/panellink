<?php

namespace Tests\Feature;

use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Services\AnalyticsPeriod;
use App\Services\CustomerAnalyticsService;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerAnalyticsService $customers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
        $this->customers = app(CustomerAnalyticsService::class);
    }

    private function owner(): Owner
    {
        $plan = Plan::create([
            'name' => 'Test', 'slug' => 'test-'.uniqid(), 'max_members' => 100,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1,
            'features' => ['hotspot'],
            'max_workspaces' => 0, 'max_rooms' => 0, 'max_products' => 0,
        ]);

        return Owner::create([
            'name' => 'Owner', 'email' => 'o'.uniqid().'@t.local', 'password' => 'secret123',
            'business_name' => 'Space', 'plan_id' => $plan->id, 'is_active' => true,
            'subscription_starts_at' => now(), 'subscription_expires_at' => now()->addMonth(),
        ]);
    }

    private function memberCreatedAt(Owner $owner, string $createdAt): HotspotUser
    {
        $member = HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Member', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);

        // Bypasses Eloquent's auto-timestamping — a plain query-builder
        // update writes the literal value, so the record can be backdated
        // for period tests.
        HotspotUser::where('id', $member->id)->update(['created_at' => $createdAt]);

        return $member->fresh();
    }

    public function test_total_customers_is_scoped_to_the_owner(): void
    {
        $owner = $this->owner();
        $other = $this->owner();
        $this->memberCreatedAt($owner, '2026-08-01 10:00:00');
        $this->memberCreatedAt($owner, '2026-08-05 10:00:00');
        $this->memberCreatedAt($other, '2026-08-05 10:00:00');

        $this->assertSame(2, $this->customers->totalCustomers($owner));
        $this->assertSame(1, $this->customers->totalCustomers($other));
    }

    public function test_new_customers_counts_only_those_created_within_the_period(): void
    {
        $owner = $this->owner();
        $this->memberCreatedAt($owner, '2026-07-31 23:59:59'); // just before
        $this->memberCreatedAt($owner, '2026-08-01 00:00:00'); // in period
        $this->memberCreatedAt($owner, '2026-08-15 12:00:00'); // in period
        $this->memberCreatedAt($owner, '2026-09-01 00:00:01'); // just after

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertSame(2, $this->customers->newCustomers($owner, $period));
    }
}
