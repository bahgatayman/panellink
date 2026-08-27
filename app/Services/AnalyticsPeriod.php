<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * An immutable date window for the dashboard analytics services, plus its
 * own preceding window of equal length for "vs previous period" deltas.
 * Every analytics service method takes one of these instead of raw dates so
 * a comparison is just calling the method twice with $period and
 * $period->previous().
 */
final class AnalyticsPeriod
{
    private function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly string $label,
    ) {}

    public static function today(): self
    {
        $now = Carbon::now();

        return new self($now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today');
    }

    public static function thisWeek(): self
    {
        $now = Carbon::now();

        return new self($now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'this_week');
    }

    public static function thisMonth(): self
    {
        $now = Carbon::now();

        return new self($now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'this_month');
    }

    public static function custom(Carbon $start, Carbon $end, string $label = 'custom'): self
    {
        return new self($start->copy()->startOfDay(), $end->copy()->endOfDay(), $label);
    }

    /**
     * The immediately preceding window of equal length (in whole days) —
     * e.g. previous() of "this month" is not necessarily a full calendar
     * month, it's the same number of days shifted back. Simple and
     * consistent across every period type, matching the approved design's
     * "same calc, shifted date window" definition rather than trying to be
     * calendar-aware per period type.
     */
    public function previous(): self
    {
        // diffInDays() returns a float in this Carbon version (e.g. a
        // same-day period yields ~0.99998, not 0) — cast to int first, or
        // adding 1 and passing the float straight to subDays() rounds up
        // and silently shifts the window back by one extra day.
        $days = (int) $this->start->diffInDays($this->end) + 1;

        return new self(
            $this->start->copy()->subDays($days),
            $this->start->copy()->subDay()->endOfDay(),
            "{$this->label}_previous",
        );
    }

    public function startDate(): string
    {
        return $this->start->toDateString();
    }

    public function endDate(): string
    {
        return $this->end->toDateString();
    }
}
