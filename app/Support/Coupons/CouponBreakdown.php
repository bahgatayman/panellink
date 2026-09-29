<?php

namespace App\Support\Coupons;

/**
 * The result of CouponService::evaluate() — pure data, no writes. Preview
 * (apply/close-preview) and completion (redeemForBooking) both go through
 * evaluate() to build one of these, so they can never disagree.
 */
final class CouponBreakdown
{
    public function __construct(
        public readonly string $code,
        public readonly float $subtotal,
        public readonly float $eligibleRoomAmount,
        public readonly float $eligibleProductAmount,
        public readonly float $discount,
        public readonly float $roomDiscount,
        public readonly float $productDiscount,
    ) {}

    public function eligibleAmount(): float
    {
        return round($this->eligibleRoomAmount + $this->eligibleProductAmount, 2);
    }

    public function total(): float
    {
        return round($this->subtotal - $this->discount, 2);
    }

    /** @return array{code: string, subtotal: float, eligible_amount: float, discount: float, room_discount: float, product_discount: float, total: float} */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'subtotal' => $this->subtotal,
            'eligible_amount' => $this->eligibleAmount(),
            'discount' => $this->discount,
            'room_discount' => $this->roomDiscount,
            'product_discount' => $this->productDiscount,
            'total' => $this->total(),
        ];
    }
}
