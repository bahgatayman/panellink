<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Single source of truth for shared-session pricing — used by both
 * closePreview() and close(), which previously duplicated this formula
 * verbatim with a comment warning "must never disagree." 'minute' billing
 * is the original continuous formula (exact proportional charge, unchanged
 * for backward compatibility). 'half_hour'/'hour' charge for every started
 * block, rounded up — the only interpretation under which choosing a block
 * mode actually changes anything (a rounded-fraction-of-a-block charge would
 * be mathematically identical to per-minute billing).
 */
class SharedSessionBillingService
{
    private const UNIT_MINUTES = [
        'minute' => 1,
        'half_hour' => 30,
        'hour' => 60,
    ];

    /**
     * @return array{total_minutes: float, billed_minutes: float, total_price: float}
     */
    public function calculate(Carbon $openedAt, Carbon $closedAt, string $billingUnit, float $pricePerHour): array
    {
        $totalMinutes = round($openedAt->diffInSeconds($closedAt) / 60, 2);
        $unitMinutes = self::UNIT_MINUTES[$billingUnit] ?? 1;

        // Continuous: no blocks, exact proportional charge — today's
        // original formula, bit-for-bit.
        if ($unitMinutes === 1) {
            $totalHours = round($totalMinutes / 60, 4);

            return [
                'total_minutes' => $totalMinutes,
                'billed_minutes' => $totalMinutes,
                'total_price' => round($totalHours * $pricePerHour, 2),
            ];
        }

        // Block billing: every started block counts in full, rounded up.
        // Deliberately no grace/tolerance window beyond the rounding already
        // baked into $totalMinutes above (see calculate()'s docblock) —
        // matches universal parking-lot/car-rental billing conventions.
        $blocks = (int) ceil($totalMinutes / $unitMinutes);
        $billedMinutes = $blocks * $unitMinutes;
        $totalHours = round($billedMinutes / 60, 4);

        return [
            'total_minutes' => $totalMinutes,
            'billed_minutes' => (float) $billedMinutes,
            'total_price' => round($totalHours * $pricePerHour, 2),
        ];
    }

    public function unitMinutes(string $billingUnit): int
    {
        return self::UNIT_MINUTES[$billingUnit] ?? 1;
    }
}
