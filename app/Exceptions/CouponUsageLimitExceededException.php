<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown inside a booking-completion transaction when the post-write
 * usage-count re-check finds a coupon's usage_limit/per_customer_limit was
 * exceeded, so the caller's DB::transaction() rolls back automatically —
 * mirrors BookingCapacityExceededException's role as a defense-in-depth
 * backstop, not a validation error.
 */
class CouponUsageLimitExceededException extends RuntimeException
{
    public function __construct(public readonly string $reasonKey)
    {
        parent::__construct(__('app.coupons.errors.'.$reasonKey));
    }
}
