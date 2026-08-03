<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\SubscriptionRequest;
use App\Services\SubscriptionRenewalService;
use Database\Seeders\FeatureSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DefaultPlanSignupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    private function expiredOwner(): Owner
    {
        return Owner::create([
            'name' => 'Owner', 'email' => 'o'.uniqid().'@t.local', 'password' => 'secret123',
            'business_name' => 'Space', 'is_active' => true,
            'subscription_starts_at' => now()->subMonths(2), 'subscription_expires_at' => now()->subDay(),
        ]);
    }

    public function test_new_owner_starts_on_free_plan_with_a_two_week_trial(): void
    {
        $this->post('/register', [
            'name' => 'New Owner',
            'email' => 'new@t.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'New Space',
        ])->assertRedirect('/dashboard');

        $owner = Owner::where('email', 'new@t.local')->firstOrFail();
        $free = Plan::where('slug', 'free')->firstOrFail();

        $this->assertSame($free->id, $owner->plan_id);
        $this->assertTrue($owner->isSubscriptionActive());
        $this->assertTrue(
            $owner->subscription_expires_at->between(now()->addDays(13), now()->addDays(15)),
            'Trial should expire in about two weeks.'
        );

        // Free plan's default features come along for the ride.
        $this->assertTrue($owner->hasFeature('workspace'));
        $this->assertTrue($owner->hasFeature('booking'));
    }

    public function test_choosing_the_free_plan_activates_instantly_without_a_request(): void
    {
        $owner = $this->expiredOwner();
        $free = Plan::where('slug', 'free')->firstOrFail();

        $this->actingAs($owner, 'owner')
            ->post('/subscription/request', ['plan_id' => $free->id, 'months' => 1])
            ->assertRedirect('/dashboard');

        $this->assertDatabaseCount('subscription_requests', 0);
        $this->assertTrue($owner->fresh()->isSubscriptionActive());
        $this->assertSame($free->id, $owner->fresh()->plan_id);
    }

    public function test_choosing_a_paid_plan_still_creates_a_pending_request(): void
    {
        $owner = $this->expiredOwner();
        $paid = Plan::where('slug', 'growth')->firstOrFail();

        $this->actingAs($owner, 'owner')
            ->post('/subscription/request', ['plan_id' => $paid->id, 'months' => 3])
            ->assertRedirect();

        $this->assertDatabaseHas('subscription_requests', [
            'owner_id' => $owner->id,
            'plan_id' => $paid->id,
            'status' => SubscriptionRequest::STATUS_PENDING,
        ]);
        // Paid plan is not active until an admin approves it.
        $this->assertFalse($owner->fresh()->isSubscriptionActive());
    }

    public function test_renewing_a_subscription_assigns_the_plans_features(): void
    {
        $owner = $this->expiredOwner();
        $plan = Plan::where('slug', 'growth')->firstOrFail(); // hotspot, workspace, booking, sales
        $admin = Admin::create(['name' => 'Op', 'email' => 'op@t.local', 'password' => Hash::make('secret123')]);

        app(SubscriptionRenewalService::class)->renew($owner, $plan, 1, $admin);

        $owner->refresh();
        foreach (['hotspot', 'workspace', 'booking', 'sales'] as $feature) {
            $this->assertTrue($owner->hasFeature($feature), "Expected the {$feature} feature after renewal.");
        }
    }
}
