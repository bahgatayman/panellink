<?php

namespace App\Services;

use App\Models\Owner;
use App\Models\WorkingHour;
use Carbon\Carbon;

/**
 * The single source of truth for "is the business open" — a per-owner
 * weekly schedule (WorkingHour, one row per day-of-week) that everything
 * else (booking validation, check-in, walk-in sessions, the calendar, a
 * future dashboard indicator) should consult through this class rather
 * than re-deriving open/closed logic of its own.
 *
 * An owner with zero configured WorkingHour rows is treated as fully
 * unrestricted — "not configured yet," matching this app's behavior
 * before this feature existed. The Settings save always writes all 7 rows
 * atomically, so an owner is only ever at 0 or 7 rows; there is no partial
 * state for this service to reason about.
 */
class BusinessHoursService
{
    /** Sentinel end-of-day boundary for an own-day segment that crosses midnight. */
    private const END_OF_DAY = '24:00:00';

    private const START_OF_DAY = '00:00:00';

    public function hasConfiguredHours(Owner $owner): bool
    {
        return $owner->workingHours()->exists();
    }

    /**
     * Every span of time on $date the business is open, as a list of
     * [start, end] H:i:s string pairs on $date's own clock (end may be the
     * '24:00:00' sentinel for a day that's still open at midnight).
     * Combines $date's own day-of-week row with the PREVIOUS day's
     * overnight spillover into $date's early hours, if that previous day
     * crosses midnight — e.g. a Thursday configured 22:00→02:00 means
     * Friday's effective window includes 00:00–02:00 in addition to
     * whatever Friday's own row says (even if Friday itself is closed:
     * that 00:00–02:00 span is genuinely Thursday's business hours, not
     * Friday's).
     *
     * Says nothing about hasConfiguredHours() — an owner with zero rows
     * simply has every row lookup return null and this returns [].
     * Callers that need the "unconfigured = unrestricted" behavior check
     * hasConfiguredHours() themselves (see isWithinWorkingHours()/
     * isOpenAt() below); encoding that as a fake "open 00:00-24:00"
     * segment here would make an unconfigured day indistinguishable from
     * one explicitly configured to be open all day, which future callers
     * (e.g. calendar rendering) need to tell apart.
     *
     * The returned strings are for display/consumption by callers — every
     * comparison this class itself does (isWithinWorkingHours()/
     * isOpenAt()/crossesMidnight()) goes through toMinutes(), never a raw
     * string comparison. bookings.start_time/end_time are stored without a
     * cast, as exactly whatever H:i string was submitted, with no
     * guarantee every value carries seconds — comparing a bare 'H:i'
     * against an 'H:i:s' value as plain strings is wrong (e.g. '10:00' <
     * '10:00:00' lexicographically, even though they're the same instant),
     * and MySQL's native TIME columns can round-trip either shape
     * depending on how a value was inserted. Converting to an integer
     * minutes-since-midnight sidesteps the format question entirely rather
     * than relying on every caller happening to submit matching precision.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function effectiveWindowForDate(Owner $owner, string $date): array
    {
        $carbon = Carbon::parse($date);
        $segments = [];

        $today = $this->rowFor($owner, $carbon->dayOfWeek);
        if ($today && $today->is_open) {
            $segments[] = [
                (string) $today->open_time,
                $this->crossesMidnight($today) ? self::END_OF_DAY : (string) $today->close_time,
            ];
        }

        $yesterday = $this->rowFor($owner, $carbon->copy()->subDay()->dayOfWeek);
        if ($yesterday && $yesterday->is_open && $this->crossesMidnight($yesterday)) {
            $segments[] = [self::START_OF_DAY, (string) $yesterday->close_time];
        }

        return $segments;
    }

    /**
     * True iff [start, end] falls entirely within a SINGLE open segment for
     * that date (both bounds inclusive — a booking ending exactly at
     * closing time is allowed). Deliberately not "start is in some segment
     * AND end is in some other segment" — that weaker check would wrongly
     * accept a booking spanning the closed gap between an overnight
     * spillover and the day's own later opening.
     */
    public function isWithinWorkingHours(Owner $owner, string $date, string $start, string $end): bool
    {
        if (! $this->hasConfiguredHours($owner)) {
            return true;
        }

        $startMinutes = $this->toMinutes($start);
        $endMinutes = $this->toMinutes($end);

        foreach ($this->effectiveWindowForDate($owner, $date) as [$segStart, $segEnd]) {
            if ($startMinutes >= $this->toMinutes($segStart) && $endMinutes <= $this->toMinutes($segEnd)) {
                return true;
            }
        }

        return false;
    }

    /** Is the business open at this exact instant? */
    public function isOpenAt(Owner $owner, Carbon $instant): bool
    {
        if (! $this->hasConfiguredHours($owner)) {
            return true;
        }

        $date = $instant->format('Y-m-d');
        $minutes = $instant->hour * 60 + $instant->minute;

        foreach ($this->effectiveWindowForDate($owner, $date) as [$segStart, $segEnd]) {
            if ($minutes >= $this->toMinutes($segStart) && $minutes < $this->toMinutes($segEnd)) {
                return true;
            }
        }

        return false;
    }

    public function isOpenNow(Owner $owner): bool
    {
        return $this->isOpenAt($owner, Carbon::now());
    }

    private function rowFor(Owner $owner, int $dayOfWeek): ?WorkingHour
    {
        return $owner->workingHours()->where('day_of_week', $dayOfWeek)->first();
    }

    /** A day's own row crosses midnight iff close_time <= open_time. */
    private function crossesMidnight(WorkingHour $row): bool
    {
        return $this->toMinutes((string) $row->close_time) <= $this->toMinutes((string) $row->open_time);
    }

    /**
     * Minutes since midnight for an 'H:i' or 'H:i:s' time string, or the
     * '24:00'/'24:00:00' sentinel this class uses internally for an
     * own-day segment still open at end of day. Deliberately not routed
     * through Carbon — hour 24 is out of Carbon's valid H:i range, and a
     * plain integer split avoids ever needing to special-case that
     * sentinel against a date-attachment library built for real instants.
     */
    private function toMinutes(string $time): int
    {
        if (str_starts_with($time, '24:00')) {
            return 24 * 60;
        }

        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
