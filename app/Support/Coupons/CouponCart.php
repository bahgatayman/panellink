<?php

namespace App\Support\Coupons;

use App\Models\Coupon;

/**
 * The already-computed totals a coupon discounts against — never a place
 * that recomputes a price itself. Built by CouponService::cartForBooking()/
 * cartForSession() from RoomPricingService's quote plus any Sale line items.
 */
final class CouponCart
{
    /** @param array<int, array{product_id: ?int, amount: float}> $productLines */
    public function __construct(
        public readonly ?int $roomId,
        public readonly float $roomAmount,
        public readonly array $productLines,
    ) {}

    public function roomEligible(Coupon $coupon): float
    {
        if ($this->roomId === null || ! $coupon->coversRoom($this->roomId)) {
            return 0.0;
        }

        return $this->roomAmount;
    }

    public function productsEligible(Coupon $coupon): float
    {
        return round(array_sum(array_map(
            fn (array $line) => $coupon->coversProduct($line['product_id']) ? $line['amount'] : 0.0,
            $this->productLines
        )), 2);
    }

    public function productsTotal(): float
    {
        return round(array_sum(array_column($this->productLines, 'amount')), 2);
    }

    public function subtotal(): float
    {
        return round($this->roomAmount + $this->productsTotal(), 2);
    }
}
