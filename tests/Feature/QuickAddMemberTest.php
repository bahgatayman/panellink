<?php

namespace Tests\Feature;

use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Inline "add member" used by the booking + shared-session pickers, so an owner
 * never has to abandon a half-filled booking to register a walk-in.
 */
class QuickAddMemberTest extends TestCase
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

    public function test_booking_owner_quick_adds_a_member_and_gets_it_back_for_selection(): void
    {
        $owner = $this->makeOwner(['booking']);

        $response = $this->actingAs($owner, 'owner')
            ->postJson('/users/quick', ['name' => 'Walk-in Guest', 'phone' => '01000002']);

        $response->assertCreated()
            ->assertJson(['name' => 'Walk-in Guest', 'phone' => '01000002'])
            ->assertJsonStructure(['id', 'name', 'phone']);

        $this->assertDatabaseHas('hotspot_users', [
            'owner_id' => $owner->id,
            'phone'    => '01000002',
            'status'   => 'active',
        ]);
    }

    public function test_quick_added_member_is_immediately_findable_by_the_picker_search(): void
    {
        $owner = $this->makeOwner(['booking']);

        $id = $this->actingAs($owner, 'owner')
            ->postJson('/users/quick', ['name' => 'Nadia Fouad', 'phone' => '01000003'])
            ->json('id');

        $this->actingAs($owner, 'owner')
            ->getJson('/users/search?q=Nadia')
            ->assertOk()
            ->assertJsonFragment(['id' => $id, 'phone' => '01000003']);
    }

    public function test_duplicate_phone_for_the_same_owner_is_rejected(): void
    {
        $owner = $this->makeOwner(['booking']);

        $this->actingAs($owner, 'owner')
            ->postJson('/users/quick', ['name' => 'First', 'phone' => '01000004'])
            ->assertCreated();

        $this->actingAs($owner, 'owner')
            ->postJson('/users/quick', ['name' => 'Second', 'phone' => '01000004'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');

        $this->assertSame(1, HotspotUser::where('owner_id', $owner->id)->count());
    }

    public function test_the_same_phone_may_belong_to_a_different_owner(): void
    {
        $a = $this->makeOwner(['booking']);
        $b = $this->makeOwner(['booking']);

        $this->actingAs($a, 'owner')->postJson('/users/quick', ['name' => 'A', 'phone' => '01000005'])->assertCreated();
        $this->actingAs($b, 'owner')->postJson('/users/quick', ['name' => 'B', 'phone' => '01000005'])->assertCreated();

        $this->assertSame(1, HotspotUser::where('owner_id', $a->id)->count());
        $this->assertSame(1, HotspotUser::where('owner_id', $b->id)->count());
    }

    public function test_plan_limit_is_reported_as_a_message_not_a_field_error(): void
    {
        $plan  = Plan::create([
            'name' => 'Tiny', 'slug' => 'tiny', 'max_members' => 1,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 2,
        ]);
        $owner = $this->makeOwner(['booking'], ['plan_id' => $plan->id]);

        $this->actingAs($owner, 'owner')->postJson('/users/quick', ['name' => 'One', 'phone' => '01000006'])->assertCreated();

        $this->actingAs($owner, 'owner')
            ->postJson('/users/quick', ['name' => 'Two', 'phone' => '01000007'])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'plan limit'));

        $this->assertSame(1, HotspotUser::where('owner_id', $owner->id)->count());
    }

    public function test_hotspot_owner_without_a_default_speed_profile_is_told_why(): void
    {
        $owner = $this->makeOwner(['hotspot']);

        $this->actingAs($owner, 'owner')
            ->postJson('/users/quick', ['name' => 'No Profile', 'phone' => '01000008'])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'speed profile'));

        $this->assertDatabaseMissing('hotspot_users', ['phone' => '01000008']);
    }

    public function test_owner_without_hotspot_or_booking_cannot_quick_add(): void
    {
        $owner = $this->makeOwner(['workspace']);

        $this->actingAs($owner, 'owner')
            ->postJson('/users/quick', ['name' => 'Nope', 'phone' => '01000009'])
            ->assertRedirect('/dashboard');

        $this->assertDatabaseMissing('hotspot_users', ['phone' => '01000009']);
    }

    public function test_both_booking_screens_render_the_picker_with_quick_add(): void
    {
        $owner = $this->makeOwner(['booking', 'workspace']);

        foreach (['/bookings/create', '/shared-sessions/create'] as $url) {
            $this->actingAs($owner, 'owner')->get($url)
                ->assertOk()
                ->assertSee('id="user-search"', false)
                ->assertSee('id="quick-add-submit"', false)
                ->assertSee('/users/quick', false);
        }
    }

    public function test_guests_cannot_quick_add(): void
    {
        // 302 rather than 401: the app renders JSON errors for api/* only
        // (bootstrap/app.php), so guests are redirected to the login page.
        $this->postJson('/users/quick', ['name' => 'Nope', 'phone' => '01000010'])
            ->assertRedirect('/login');

        $this->assertDatabaseMissing('hotspot_users', ['phone' => '01000010']);
    }
}
