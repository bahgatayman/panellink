<?php

namespace App\Support\Pricing;

/**
 * The answer RoomPricingService gives for "Room + people + duration".
 * Immutable; every caller (booking store/update, room quotes, shared-session
 * preview/close/estimate) reads the same fields rather than re-deriving any.
 */
final class PriceQuote
{
    public function __construct(
        public readonly string $model,
        public readonly float $totalPrice,
        /** Actual time used/booked, in minutes. */
        public readonly float $totalMinutes,
        /** Time actually charged for (block rounding or the matched package length). */
        public readonly float $billedMinutes,
        /** Stored on bookings.price_per_hour: the room rate for hourly/people models, the effective rate for packages. */
        public readonly float $ratePerHour,
        /** Human breakdown, e.g. "3 hours · 2 people"; null for plain hourly pricing. */
        public readonly ?string $note,
        /** Hourly rate the live ticker may extrapolate with, or null when the price is not linear in time. */
        public readonly ?float $liveRatePerHour,
    ) {}

    /** Booked hours as stored on bookings.total_hours (same rounding BookingService always used). */
    public function totalHours(): float
    {
        return round($this->totalMinutes / 60, 2);
    }

    public function billedHours(): float
    {
        return round($this->billedMinutes / 60, 4);
    }
}
