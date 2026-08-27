<?php

namespace Tests\Unit;

use App\Services\AnalyticsPeriod;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AnalyticsPeriodTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_today_spans_the_full_current_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-27 14:30:00'));
        $period = AnalyticsPeriod::today();

        $this->assertSame('2026-08-27 00:00:00', $period->start->toDateTimeString());
        $this->assertSame('2026-08-27 23:59:59', $period->end->toDateTimeString());
        $this->assertSame('2026-08-27', $period->startDate());
        $this->assertSame('2026-08-27', $period->endDate());
    }

    public function test_this_month_spans_the_full_calendar_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-15 09:00:00'));
        $period = AnalyticsPeriod::thisMonth();

        $this->assertSame('2026-08-01', $period->startDate());
        $this->assertSame('2026-08-31', $period->endDate());
    }

    public function test_previous_of_a_single_day_period_is_the_day_before(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-27 12:00:00'));
        $previous = AnalyticsPeriod::today()->previous();

        $this->assertSame('2026-08-26', $previous->startDate());
        $this->assertSame('2026-08-26', $previous->endDate());
    }

    public function test_previous_of_a_multi_day_period_shifts_back_by_the_same_length(): void
    {
        // A custom 10-day window (inclusive) — previous() must be the
        // immediately preceding 10-day window, not a calendar-aware shift.
        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-10'), Carbon::parse('2026-08-19'));
        $previous = $period->previous();

        $this->assertSame('2026-07-31', $previous->startDate());
        $this->assertSame('2026-08-09', $previous->endDate());
    }

    public function test_previous_does_not_mutate_the_original_period(): void
    {
        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-10'), Carbon::parse('2026-08-19'));
        $period->previous();

        $this->assertSame('2026-08-10', $period->startDate());
        $this->assertSame('2026-08-19', $period->endDate());
    }
}
