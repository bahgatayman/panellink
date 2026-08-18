<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;

/**
 * 12h-label/24h-value <select> options, shared by the booking form and the
 * working-hours settings form so there's exactly one place this pairing is
 * generated rather than two independently-hardcoded copies.
 */
trait GeneratesTimeSlots
{
    /**
     * @return array<string, string> 'H:i' value => 'h:i A' label
     */
    private function generateTimeSlots(string $start = '06:00', string $end = '23:30', int $stepMinutes = 30): array
    {
        $slots = [];
        $cursor = Carbon::createFromFormat('H:i', $start);
        $endTime = Carbon::createFromFormat('H:i', $end);

        while ($cursor->lte($endTime)) {
            $slots[$cursor->format('H:i')] = $cursor->format('h:i A');
            $cursor->addMinutes($stepMinutes);
        }

        return $slots;
    }
}
