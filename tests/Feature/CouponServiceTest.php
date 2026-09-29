<?php

namespace Tests\Feature;

use App\Exceptions\CouponRejectedException;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Room;
use App\Models\Workspace;
use App\Services\CouponService;
use App\Support\Coupons\CouponCart;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private CouponService $coupons;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
        $this->coupons = app(CouponService::class);
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

    private function room(Owner $owner): Room
    {
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);

        return Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Room A',
            'type' => 'meeting', 'capacity' => 4, 'price_per_hour' => 50,
        ]);
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Member', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    private function product(Owner $owner, string $name = 'Coffee'): Product
    {
        return Product::create(['owner_id' => $owner->id, 'name' => $name, 'type' => 'product', 'price' => 10, 'is_active' => true]);
    }

    private function coupon(Owner $owner, array $attrs = []): Coupon
    {
        return Coupon::create(array_merge([
            'owner_id' => $owner->id,
            'code' => 'CODE'.uniqid(),
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 20,
            'applies_to' => Coupon::SCOPE_BOTH,
            'is_active' => true,
        ], $attrs));
    }

    /** A cart matching the spec's own worked example: Room 500, Coffee 80, Water 30. */
    private function workedExampleCart(int $roomId): CouponCart
    {
        return new CouponCart($roomId, 500.0, [
            ['product_id' => 1, 'amount' => 80.0],
            ['product_id' => 2, 'amount' => 30.0],
        ]);
    }

    // --- find() ---

    public function test_find_rejects_a_missing_code(): void
    {
        $owner = $this->owner();

        $this->expectException(CouponRejectedException::class);
        $this->coupons->find($owner->id, 'NOPE');
    }

    public function test_find_rejects_another_owners_code_identically_to_a_missing_one(): void
    {
        $owner = $this->owner();
        $other = $this->owner();
        $coupon = $this->coupon($other, ['code' => 'OTHER20']);

        try {
            $this->coupons->find($owner->id, 'OTHER20');
            $this->fail('Expected CouponRejectedException');
        } catch (CouponRejectedException $e) {
            $this->assertSame('not_found', $e->reasonKey);
        }

        try {
            $this->coupons->find($owner->id, 'DOES-NOT-EXIST');
            $this->fail('Expected CouponRejectedException');
        } catch (CouponRejectedException $e) {
            $this->assertSame('not_found', $e->reasonKey);
        }
    }

    public function test_find_normalizes_the_code(): void
    {
        $owner = $this->owner();
        $this->coupon($owner, ['code' => 'SUMMER10']);

        $coupon = $this->coupons->find($owner->id, '  summer10  ');

        $this->assertSame('SUMMER10', $coupon->code);
    }

    // --- evaluate(): rejection reasons ---

    public function test_evaluate_rejects_an_inactive_coupon(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['is_active' => false]);

        try {
            $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));
            $this->fail('Expected rejection');
        } catch (CouponRejectedException $e) {
            $this->assertSame('inactive', $e->reasonKey);
        }
    }

    public function test_evaluate_rejects_a_scheduled_coupon(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['starts_at' => now()->addDay()]);

        try {
            $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));
            $this->fail('Expected rejection');
        } catch (CouponRejectedException $e) {
            $this->assertSame('scheduled', $e->reasonKey);
        }
    }

    public function test_evaluate_rejects_an_expired_coupon(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['expires_at' => now()->subDay()]);

        try {
            $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));
            $this->fail('Expected rejection');
        } catch (CouponRejectedException $e) {
            $this->assertSame('expired', $e->reasonKey);
        }
    }

    public function test_evaluate_rejects_a_coupon_out_of_scope_for_the_cart(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $otherRoom = $this->room($owner);
        $coupon = $this->coupon($owner, ['applies_to' => Coupon::SCOPE_ROOMS]);
        $coupon->rooms()->attach($otherRoom->id); // specific, but not this booking's room

        $cart = new CouponCart($room->id, 500.0, []);

        try {
            $this->coupons->evaluate($coupon, $cart);
            $this->fail('Expected rejection');
        } catch (CouponRejectedException $e) {
            $this->assertSame('out_of_scope', $e->reasonKey);
        }
    }

    public function test_evaluate_rejects_below_minimum_spend(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['minimum_spend' => 1000]);

        try {
            $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));
            $this->fail('Expected rejection');
        } catch (CouponRejectedException $e) {
            $this->assertSame('minimum_spend', $e->reasonKey);
        }
    }

    public function test_evaluate_rejects_once_the_usage_limit_is_reached(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['usage_limit' => 1]);

        CouponUsage::create([
            'coupon_id' => $coupon->id, 'owner_id' => $owner->id, 'booking_id' => null,
            'original_amount' => 100, 'discount_amount' => 20, 'final_amount' => 80,
            'room_discount' => 20, 'product_discount' => 0, 'used_at' => now(),
        ]);

        try {
            $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));
            $this->fail('Expected rejection');
        } catch (CouponRejectedException $e) {
            $this->assertSame('limit_reached', $e->reasonKey);
        }
    }

    public function test_evaluate_rejects_once_the_per_customer_limit_is_reached(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $member = $this->member($owner);
        $otherMember = $this->member($owner);
        $coupon = $this->coupon($owner, ['per_customer_limit' => 1]);

        CouponUsage::create([
            'coupon_id' => $coupon->id, 'owner_id' => $owner->id, 'booking_id' => null,
            'hotspot_user_id' => $member->id,
            'original_amount' => 100, 'discount_amount' => 20, 'final_amount' => 80,
            'room_discount' => 20, 'product_discount' => 0, 'used_at' => now(),
        ]);

        try {
            $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id), hotspotUserId: $member->id);
            $this->fail('Expected rejection');
        } catch (CouponRejectedException $e) {
            $this->assertSame('customer_limit_reached', $e->reasonKey);
        }

        // A different customer is unaffected.
        $breakdown = $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id), hotspotUserId: $otherMember->id);
        $this->assertGreaterThan(0, $breakdown->discount);
    }

    public function test_evaluate_skips_the_per_customer_limit_when_there_is_no_customer(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['per_customer_limit' => 1]);

        $breakdown = $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id), hotspotUserId: null);

        $this->assertGreaterThan(0, $breakdown->discount);
    }

    // --- The spec's own worked example ---

    public function test_rooms_only_coupon_discounts_only_the_room_line(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['applies_to' => Coupon::SCOPE_ROOMS, 'discount_type' => Coupon::TYPE_PERCENTAGE, 'discount_value' => 20]);

        $breakdown = $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));

        $this->assertSame(610.0, $breakdown->subtotal);
        $this->assertSame(500.0, $breakdown->eligibleAmount());
        $this->assertSame(100.0, $breakdown->discount);
        $this->assertSame(100.0, $breakdown->roomDiscount);
        $this->assertSame(0.0, $breakdown->productDiscount);
        $this->assertSame(510.0, $breakdown->total());
    }

    public function test_products_only_coupon_discounts_only_the_product_lines(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['applies_to' => Coupon::SCOPE_PRODUCTS, 'discount_type' => Coupon::TYPE_PERCENTAGE, 'discount_value' => 20]);

        $breakdown = $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));

        $this->assertSame(110.0, $breakdown->eligibleAmount());
        $this->assertSame(22.0, $breakdown->discount);
        $this->assertSame(0.0, $breakdown->roomDiscount);
        $this->assertSame(22.0, $breakdown->productDiscount);
        $this->assertSame(588.0, $breakdown->total());
    }

    public function test_both_scope_coupon_splits_the_discount_proportionally(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['applies_to' => Coupon::SCOPE_BOTH, 'discount_type' => Coupon::TYPE_PERCENTAGE, 'discount_value' => 20]);

        $breakdown = $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));

        $this->assertSame(610.0, $breakdown->eligibleAmount());
        $this->assertSame(122.0, $breakdown->discount);
        $this->assertSame(100.0, $breakdown->roomDiscount);
        $this->assertSame(22.0, $breakdown->productDiscount);
        $this->assertSame(488.0, $breakdown->total());
    }

    public function test_fixed_discount_is_clamped_to_the_eligible_amount(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['applies_to' => Coupon::SCOPE_PRODUCTS, 'discount_type' => Coupon::TYPE_FIXED, 'discount_value' => 1000]);

        $breakdown = $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));

        // Eligible (products) is 110 — a 1000 fixed discount must clamp, never go negative.
        $this->assertSame(110.0, $breakdown->discount);
        $this->assertSame(500.0, $breakdown->total()); // 610 - 110
    }

    public function test_fixed_discount_under_the_eligible_amount_is_not_clamped(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['applies_to' => Coupon::SCOPE_ROOMS, 'discount_type' => Coupon::TYPE_FIXED, 'discount_value' => 50]);

        $breakdown = $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));

        $this->assertSame(50.0, $breakdown->discount);
        $this->assertSame(560.0, $breakdown->total());
    }

    public function test_a_coupon_restricted_to_specific_rooms_only_covers_those_rooms(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $coupon = $this->coupon($owner, ['applies_to' => Coupon::SCOPE_ROOMS]);
        $coupon->rooms()->attach($room->id);

        $breakdown = $this->coupons->evaluate($coupon, $this->workedExampleCart($room->id));

        $this->assertSame(500.0, $breakdown->eligibleAmount());
    }

    public function test_multiple_eligible_product_lines_sum_correctly(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $productA = $this->product($owner, 'Coffee');
        $productB = $this->product($owner, 'Water');
        $coupon = $this->coupon($owner, ['applies_to' => Coupon::SCOPE_PRODUCTS]);

        $cart = new CouponCart($room->id, 500.0, [
            ['product_id' => $productA->id, 'amount' => 80.0],
            ['product_id' => $productB->id, 'amount' => 30.0],
        ]);

        $breakdown = $this->coupons->evaluate($coupon, $cart);

        $this->assertSame(110.0, $breakdown->eligibleAmount());
    }

    public function test_a_specific_products_coupon_excludes_products_not_targeted(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $productA = $this->product($owner, 'Coffee');
        $productB = $this->product($owner, 'Water');
        $coupon = $this->coupon($owner, ['applies_to' => Coupon::SCOPE_PRODUCTS]);
        $coupon->products()->attach($productA->id);

        $cart = new CouponCart($room->id, 500.0, [
            ['product_id' => $productA->id, 'amount' => 80.0],
            ['product_id' => $productB->id, 'amount' => 30.0],
        ]);

        $breakdown = $this->coupons->evaluate($coupon, $cart);

        $this->assertSame(80.0, $breakdown->eligibleAmount());
    }
}
