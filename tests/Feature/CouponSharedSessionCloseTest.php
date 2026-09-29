<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Role;
use App\Models\Room;
use App\Models\Sale;
use App\Models\SharedSession;
use App\Models\Staff;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponSharedSessionCloseTest extends TestCase
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

    private function openSession(Owner $owner): SharedSession
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
            'session_date' => today()->toDateString(), 'start_time' => '10:00',
            'opened_at' => now()->subHour(), 'status' => 'open',
        ]);
    }

    private function coupon(Owner $owner, array $attrs = []): Coupon
    {
        return Coupon::create(array_merge([
            'owner_id' => $owner->id,
            'code' => 'SESSION20',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 20,
            'applies_to' => Coupon::SCOPE_BOTH,
            'is_active' => true,
        ], $attrs));
    }

    private function staff(Owner $owner, string $roleKey = 'receptionist'): Staff
    {
        $role = Role::whereNull('owner_id')->where('key', $roleKey)->firstOrFail();

        $staff = Staff::create([
            'owner_id' => $owner->id, 'role_id' => $role->id,
            'name' => 'Staffer', 'email' => 's'.uniqid().'@t.local',
            'password' => 'secret123', 'is_active' => true,
        ]);
        $staff->syncPermissionsFromRole();

        return $staff;
    }

    public function test_close_preview_with_a_valid_code_matches_what_close_actually_charges(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00'));
        $owner = $this->owner();
        $session = $this->openSession($owner); // 1 hour @ 60/hr
        $this->coupon($owner);

        $preview = $this->actingAs($owner, 'owner')
            ->getJson("/shared-sessions/{$session->id}/close-preview?coupon_code=SESSION20")
            ->assertOk();

        $preview->assertJsonPath('coupon.discount', 12); // 20% of 60
        $preview->assertJsonPath('grand_total', '48.00');

        $close = $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close", ['coupon_code' => 'SESSION20'])
            ->assertOk();

        $booking = $session->fresh()->booking;
        $this->assertSame(48.0, (float) $booking->amount_paid);
        $this->assertSame(12.0, (float) $booking->discount_total);
        $this->assertStringContainsString('48.00', $close->json('message'));
    }

    public function test_closing_with_a_coupon_records_usage_with_the_net_amount_paid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00'));
        $owner = $this->owner();
        $session = $this->openSession($owner);
        $this->coupon($owner);

        $this->actingAs($owner, 'owner')->postJson("/shared-sessions/{$session->id}/close", ['coupon_code' => 'SESSION20'])->assertOk();

        $booking = $session->fresh()->booking;
        $usage = CouponUsage::where('booking_id', $booking->id)->first();
        $this->assertNotNull($usage);
        $this->assertSame(60.0, (float) $usage->original_amount);
        $this->assertSame(12.0, (float) $usage->discount_amount);
        $this->assertSame(48.0, (float) $usage->final_amount);
        $this->assertSame(48.0, (float) $booking->amount_paid);
        $this->assertSame('paid', $booking->payment_status);
    }

    public function test_an_invalid_code_at_close_returns_422_and_leaves_the_session_open(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00'));
        $owner = $this->owner();
        $session = $this->openSession($owner);

        $response = $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close", ['coupon_code' => 'NOPE']);

        $response->assertStatus(422);
        $session->refresh();
        $this->assertSame('open', $session->status);
        $this->assertNull($session->booking_id);
        $this->assertSame(0, CouponUsage::count());
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_an_exhausted_coupon_at_close_returns_422_and_leaves_the_session_open(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00'));
        $owner = $this->owner();
        $session = $this->openSession($owner);
        $this->coupon($owner, ['usage_limit' => 1]);
        CouponUsage::create([
            'coupon_id' => Coupon::where('owner_id', $owner->id)->first()->id, 'owner_id' => $owner->id,
            'original_amount' => 10, 'discount_amount' => 2, 'final_amount' => 8,
            'room_discount' => 2, 'product_discount' => 0, 'used_at' => now(),
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close", ['coupon_code' => 'SESSION20']);

        $response->assertStatus(422);
        $this->assertSame('open', $session->fresh()->status);
    }

    public function test_double_close_with_a_coupon_leaves_exactly_one_usage(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00'));
        $owner = $this->owner();
        $session = $this->openSession($owner);
        $this->coupon($owner);

        $this->actingAs($owner, 'owner')->postJson("/shared-sessions/{$session->id}/close", ['coupon_code' => 'SESSION20'])->assertOk();
        $second = $this->actingAs($owner, 'owner')->postJson("/shared-sessions/{$session->id}/close", ['coupon_code' => 'SESSION20']);

        $second->assertStatus(409);
        $this->assertSame(1, CouponUsage::count());
    }

    public function test_session_tab_items_transfer_with_their_own_discount_intact(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00'));
        $owner = $this->owner();
        $session = $this->openSession($owner);
        $product = Product::create(['owner_id' => $owner->id, 'name' => 'Coffee', 'type' => 'product', 'price' => 40, 'is_active' => true, 'track_stock' => false]);
        $this->coupon($owner, ['applies_to' => Coupon::SCOPE_BOTH]);

        $this->actingAs($owner, 'owner')->postJson("/shared-sessions/{$session->id}/items", ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($owner, 'owner')->postJson("/shared-sessions/{$session->id}/close", ['coupon_code' => 'SESSION20'])->assertOk();

        $booking = $session->fresh()->booking;
        $sale = Sale::where('booking_id', $booking->id)->first();

        // Subtotal 100 (60 room + 40 product), 20% both = 20 total discount,
        // split proportionally: room 12, product 8.
        $this->assertSame(12.0, (float) $booking->discount_total);
        $this->assertSame(8.0, (float) $sale->discount_total);
        $this->assertSame(32.0, (float) $sale->total); // 40 - 8
        $this->assertSame(80.0, $booking->grandTotal()); // 48 + 32
    }

    public function test_staff_without_coupons_apply_cannot_close_with_a_code(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00'));
        $owner = $this->owner();
        $session = $this->openSession($owner);
        $this->coupon($owner);
        $staff = $this->staff($owner, 'receptionist');
        $staff->permissions()->detach(Permission::where('key', 'coupons.apply')->value('id'));

        $response = $this->actingAs($staff, 'staff')
            ->postJson("/shared-sessions/{$session->id}/close", ['coupon_code' => 'SESSION20']);

        $response->assertStatus(403);
        $this->assertSame('open', $session->fresh()->status);
    }
}
