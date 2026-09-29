<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by CouponService whenever a code can't be applied — inactive,
 * scheduled, expired, exhausted, wrong owner, out of scope, below minimum
 * spend, etc. The message is always a translated, user-friendly string (never
 * a raw validator/internal detail) so callers can show it directly.
 */
class CouponRejectedException extends RuntimeException
{
    public function __construct(public readonly string $reasonKey, array $replace = [])
    {
        parent::__construct(__('app.coupons.errors.'.$reasonKey, $replace));
    }
}
