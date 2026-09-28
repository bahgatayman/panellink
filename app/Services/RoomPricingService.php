<?php

namespace App\Services;

use App\Models\Owner;
use App\Models\Room;
use App\Models\SharedSession;
use App\Support\Money;
use App\Support\Pricing\PriceQuote;
use App\Support\Pricing\PricingRules;
use Carbon\Carbon;

/**
 * THE source of truth for "Room + number of people + duration → price".
 * Every place that charges or quotes goes through here: booking create/edit,
 * room quotes (booking form cards, availability lookup), shared-session
 * estimate/preview/close and check-in snapshots. Financials only ever read
 * the totals this service produced and that were stored on bookings — they
 * never re-price.
 *
 * Hourly rooms (every room that existed before flexible pricing) are
 * delegated to the two original formulas, unchanged:
 *   bookings        → BookingService::calculateBooking() (hours × rate)
 *   shared sessions → SharedSessionBillingService::calculate() (billing_unit blocks)
 *
 * Rule-based rooms (PricingRules):
 *   · People tier  = the first tier whose upper bound fits the party; larger
 *     parties use the last tier.
 *   · people model = time × that tier's hourly rate.
 *   · duration / people_duration = the cheapest owner-defined duration option
 *     that covers the time (so 90 min with 1h/2h options is the 2h price —
 *     every started package counts, like block billing). Full Day is an option
 *     whose length is that date's business day (BusinessHoursService), or the
 *     whole calendar day when hours aren't configured — so it also acts as the
 *     day's price cap. Longer than every option (no Full Day): the longest
 *     option plus the extra time at that option's own per-minute rate.
 */
class RoomPricingService
{
    public function __construct(
        private BookingService $bookings,
        private SharedSessionBillingService $billing,
        private BusinessHoursService $businessHours,
    ) {}

    /** Price a reservation of $room for $people between $start and $end on $date. */
    public function quoteBooking(Room $room, int $people, string $date, string $start, string $end): PriceQuote
    {
        $rules = $room->pricingRules();

        if ($rules->isHourly()) {
            $calc = $this->bookings->calculateBooking($start, $end, (float) $room->price_per_hour);
            $minutes = $calc['total_hours'] * 60;

            return new PriceQuote(PricingRules::HOURLY, $calc['total_price'], $minutes, $minutes,
                (float) $room->price_per_hour, null, (float) $room->price_per_hour);
        }

        $minutes = (float) Carbon::parse($start)->diffInMinutes(Carbon::parse($end));
        if ($minutes <= 0) {
            throw new \InvalidArgumentException('End time must be after start time.');
        }

        return $this->quoteRules($rules, max(1, $people), $minutes, $this->fullDayMinutes($room->owner, $date));
    }

    /**
     * Running or final bill for a shared session, from opened_at → $closedAt.
     * Uses the rules snapshotted when the session opened (never the room's
     * current rules) — or, for a session with no snapshot, the legacy
     * snapshotted billing_unit/billed_price_per_hour path bit-for-bit.
     */
    public function quoteSession(SharedSession $session, Carbon $closedAt): PriceQuote
    {
        if (! $session->pricing_snapshot) {
            $unit = $session->billing_unit ?? 'minute';
            $rate = (float) ($session->billed_price_per_hour ?? $session->room->price_per_hour);
            $billed = $this->billing->calculate($session->opened_at, $closedAt, $unit, $rate);

            return new PriceQuote(PricingRules::HOURLY, $billed['total_price'], $billed['total_minutes'],
                $billed['billed_minutes'], $rate, null, $unit === 'minute' ? $rate : null);
        }

        $rules = PricingRules::fromStored($session->pricing_snapshot['model'] ?? null, $session->pricing_snapshot['rules'] ?? null);
        $minutes = round($session->opened_at->diffInSeconds($closedAt) / 60, 2);
        $date = $session->session_date?->format('Y-m-d') ?? $session->opened_at->format('Y-m-d');

        return $this->quoteRules($rules, max(1, (int) $session->party_size), $minutes, $this->fullDayMinutes($session->room->owner, $date));
    }

    /** What a session opened now must freeze; null keeps the legacy hourly path. */
    public function snapshotFor(Room $room): ?array
    {
        $rules = $room->pricingRules();

        return $rules->isHourly() ? null : ['model' => $rules->model, 'rules' => $rules->toArray()];
    }

    /** Minutes "Full Day" stands for on $date for this owner. */
    public function fullDayMinutes(?Owner $owner, string $date): int
    {
        $window = $owner ? $this->businessHours->fullDayWindow($owner, $date) : null;

        return $window['minutes'] ?? 24 * 60;
    }

    /** Short, owner-facing summary for room lists and cards: "EGP 100.00/hr", "From EGP 50.00". */
    public function summary(Room $room): string
    {
        $rules = $room->pricingRules();

        return match ($rules->model) {
            PricingRules::HOURLY => Money::format((float) $room->price_per_hour).__('app.common.slash_hr'),
            PricingRules::PEOPLE => __('app.pricing.from', ['price' => Money::format($rules->lowestPrice())]).__('app.common.slash_hr'),
            default => __('app.pricing.from', ['price' => Money::format($rules->lowestPrice())]),
        };
    }

    private function quoteRules(PricingRules $rules, int $people, float $minutes, int $fullDayMinutes): PriceQuote
    {
        $row = $rules->tierIndexFor($people);
        $peopleNote = $rules->usesPeople() ? $rules->peopleLabel($row) : null;

        // Time-based rate per tier (people model).
        if (! $rules->usesDurations()) {
            $rate = (float) $rules->prices[$row][0];
            $total = round(round($minutes / 60, 4) * $rate, 2);

            return new PriceQuote($rules->model, $total, $minutes, $minutes, $rate,
                $peopleNote.' · '.Money::format($rate).__('app.common.slash_hr'), $rate);
        }

        // Package-based (duration, people_duration).
        $lengths = [];
        foreach ($rules->durations as $col => $d) {
            $lengths[$col] = $rules->isFullDay($col) ? $fullDayMinutes : (int) $d['minutes'];
        }

        $best = null;
        foreach ($lengths as $col => $length) {
            if ($length >= $minutes) {
                $price = (float) $rules->prices[$row][$col];
                if ($best === null || $price < $best['price'] || ($price === $best['price'] && $length < $best['length'])) {
                    $best = ['col' => $col, 'price' => $price, 'length' => $length];
                }
            }
        }

        if ($best) {
            $total = round($best['price'], 2);
            $billed = (float) $best['length'];
            $note = $rules->durationLabel($best['col']);
        } else {
            // Longer than every option and no Full Day covers it: the longest
            // option plus the extra time pro-rata at that option's own rate.
            $col = array_keys($lengths, max($lengths))[0];
            $length = $lengths[$col];
            $price = (float) $rules->prices[$row][$col];
            $extra = $minutes - $length;
            $total = round($price + $extra * ($price / $length), 2);
            $billed = $minutes;
            $note = __('app.pricing.note_extra', [
                'base' => $rules->durationLabel($col),
                'extra' => PricingRules::minutesLabel((int) ceil($extra)),
            ]);
        }

        $rate = $minutes > 0 ? round($total / ($minutes / 60), 2) : 0.0;

        return new PriceQuote($rules->model, $total, $minutes, $billed, $rate,
            $peopleNote ? $note.' · '.$peopleNote : $note, null);
    }
}
