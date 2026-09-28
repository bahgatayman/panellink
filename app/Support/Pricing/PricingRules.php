<?php

namespace App\Support\Pricing;

/**
 * An owner's pricing rules for one room — pure data + normalisation +
 * validation, no money math (that lives only in RoomPricingService).
 *
 * Shape (also the JSON stored in rooms.pricing_rules and
 * shared_sessions.pricing_snapshot):
 *   durations: [{minutes: 60}, {minutes: 180}, {full_day: true}]  // columns
 *   people:    [{max: 2}, {max: 4}, {max: 6}]                     // rows; tier i covers (prev max + 1)..max
 *   prices:    [[row0col0, row0col1, …], …]                       // rows × columns
 *
 * Every model uses the same grid, only its dimensions differ:
 *   hourly           → no rules at all (rooms.price_per_hour, the legacy path)
 *   duration         → 1 row × N durations        (a flat price per package)
 *   people           → N tiers × 1 column         (an hourly rate per tier)
 *   people_duration  → N tiers × N durations      (a flat price per cell)
 *
 * Nothing about specific durations or people counts is hard-coded: the
 * owner defines every option. Durations are kept sorted shortest-first
 * with Full Day last; tiers are kept sorted by their upper bound.
 */
final class PricingRules
{
    public const HOURLY = 'hourly';

    public const DURATION = 'duration';

    public const PEOPLE = 'people';

    public const PEOPLE_DURATION = 'people_duration';

    public const MODELS = [self::HOURLY, self::DURATION, self::PEOPLE, self::PEOPLE_DURATION];

    public const MIN_DURATION_MINUTES = 15;

    public const MAX_DURATION_MINUTES = 1440;

    public const MAX_OPTIONS = 12;

    /**
     * @param  array<int, array{minutes?: int, full_day?: bool}>  $durations
     * @param  array<int, array{max: int}>  $people
     * @param  array<int, array<int, float>>  $prices
     */
    private function __construct(
        public readonly string $model,
        public readonly array $durations,
        public readonly array $people,
        public readonly array $prices,
    ) {}

    public static function hourly(): self
    {
        return new self(self::HOURLY, [], [], []);
    }

    /** Rebuild from already-validated stored data (rooms.pricing_rules / a session snapshot). */
    public static function fromStored(?string $model, ?array $rules): self
    {
        $model = in_array($model, self::MODELS, true) ? $model : self::HOURLY;
        if ($model === self::HOURLY || ! $rules) {
            return self::hourly();
        }

        return new self(
            $model,
            array_values($rules['durations'] ?? []),
            array_values($rules['people'] ?? []),
            array_map(fn ($row) => array_map('floatval', array_values($row)), array_values($rules['prices'] ?? [])),
        );
    }

    /**
     * Parse + validate the room form's submission. Returns the normalised
     * rules and a list of translated error messages (empty when valid).
     *
     * @return array{0: self, 1: array<int, string>}
     */
    public static function fromInput(string $model, mixed $raw): array
    {
        if (! in_array($model, self::MODELS, true)) {
            return [self::hourly(), [__('app.pricing.errors.model')]];
        }
        if ($model === self::HOURLY) {
            return [self::hourly(), []];
        }

        $data = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($data)) {
            return [self::hourly(), [__('app.pricing.errors.invalid')]];
        }

        $errors = [];
        $usesDurations = in_array($model, [self::DURATION, self::PEOPLE_DURATION], true);
        $usesPeople = in_array($model, [self::PEOPLE, self::PEOPLE_DURATION], true);

        // --- Duration options (columns) ---
        $durations = [];
        if ($usesDurations) {
            foreach ((array) ($data['durations'] ?? []) as $d) {
                if (! empty($d['full_day'])) {
                    $durations[] = ['full_day' => true];

                    continue;
                }
                $minutes = filter_var($d['minutes'] ?? null, FILTER_VALIDATE_INT);
                if ($minutes === false || $minutes < self::MIN_DURATION_MINUTES || $minutes > self::MAX_DURATION_MINUTES) {
                    $errors[] = __('app.pricing.errors.duration_range');

                    continue;
                }
                $durations[] = ['minutes' => $minutes];
            }
            if ($durations === []) {
                $errors[] = __('app.pricing.errors.duration_required');
            }
            $keys = array_map(fn ($d) => isset($d['full_day']) ? 'full_day' : $d['minutes'], $durations);
            if (count($keys) !== count(array_unique($keys))) {
                $errors[] = __('app.pricing.errors.duration_duplicate');
            }
        } else {
            $durations = [];
        }

        // --- People tiers (rows) ---
        $people = [];
        if ($usesPeople) {
            foreach ((array) ($data['people'] ?? []) as $p) {
                $max = filter_var($p['max'] ?? null, FILTER_VALIDATE_INT);
                if ($max === false || $max < 1 || $max > 999) {
                    $errors[] = __('app.pricing.errors.people_range');

                    continue;
                }
                $people[] = ['max' => $max];
            }
            if ($people === []) {
                $errors[] = __('app.pricing.errors.people_required');
            }
            $maxes = array_column($people, 'max');
            if (count($maxes) !== count(array_unique($maxes))) {
                $errors[] = __('app.pricing.errors.people_duplicate');
            }
        }

        if (count($durations) > self::MAX_OPTIONS || count($people) > self::MAX_OPTIONS) {
            $errors[] = __('app.pricing.errors.too_many', ['max' => self::MAX_OPTIONS]);
        }

        // --- Price grid: exactly rows × columns, every cell a price ≥ 0 ---
        $rowCount = $usesPeople ? count($people) : 1;
        $colCount = $usesDurations ? count($durations) : 1;
        $rawPrices = array_values((array) ($data['prices'] ?? []));
        $prices = [];
        $missing = false;
        for ($r = 0; $r < $rowCount; $r++) {
            $row = array_values((array) ($rawPrices[$r] ?? []));
            for ($c = 0; $c < $colCount; $c++) {
                $v = $row[$c] ?? null;
                if ($v === null || $v === '' || ! is_numeric($v) || (float) $v < 0 || (float) $v > 1_000_000) {
                    $missing = true;
                    $prices[$r][$c] = 0.0;
                } else {
                    $prices[$r][$c] = round((float) $v, 2);
                }
            }
        }
        if ($missing) {
            $errors[] = __('app.pricing.errors.price_missing');
        }

        if ($errors) {
            return [self::hourly(), array_values(array_unique($errors))];
        }

        // --- Normalise order, carrying the grid along with it ---
        $colOrder = array_keys($durations);
        usort($colOrder, function ($a, $b) use ($durations) {
            $la = isset($durations[$a]['full_day']) ? PHP_INT_MAX : $durations[$a]['minutes'];
            $lb = isset($durations[$b]['full_day']) ? PHP_INT_MAX : $durations[$b]['minutes'];

            return $la <=> $lb;
        });
        $rowOrder = array_keys($people);
        usort($rowOrder, fn ($a, $b) => $people[$a]['max'] <=> $people[$b]['max']);
        if (! $usesDurations) {
            $colOrder = [0];
        }
        if (! $usesPeople) {
            $rowOrder = [0];
        }

        $sortedPrices = [];
        foreach ($rowOrder as $r) {
            $sortedPrices[] = array_map(fn ($c) => $prices[$r][$c], $colOrder);
        }

        return [new self(
            $model,
            $usesDurations ? array_map(fn ($c) => $durations[$c], $colOrder) : [],
            $usesPeople ? array_map(fn ($r) => $people[$r], $rowOrder) : [],
            $sortedPrices,
        ), []];
    }

    public function isHourly(): bool
    {
        return $this->model === self::HOURLY;
    }

    public function usesDurations(): bool
    {
        return in_array($this->model, [self::DURATION, self::PEOPLE_DURATION], true);
    }

    public function usesPeople(): bool
    {
        return in_array($this->model, [self::PEOPLE, self::PEOPLE_DURATION], true);
    }

    /** Stored form; null for hourly (nothing to store). */
    public function toArray(): ?array
    {
        if ($this->isHourly()) {
            return null;
        }

        return ['durations' => $this->durations, 'people' => $this->people, 'prices' => $this->prices];
    }

    /**
     * Row for a party size: the first tier whose upper bound fits the party.
     * A party larger than every tier is priced at the last (largest) tier —
     * never an unpriceable booking.
     */
    public function tierIndexFor(int $people): int
    {
        if (! $this->usesPeople()) {
            return 0;
        }
        foreach ($this->people as $i => $tier) {
            if ($people <= $tier['max']) {
                return $i;
            }
        }

        return count($this->people) - 1;
    }

    /** @return array{0: int, 1: int} inclusive [min, max] people for tier $i */
    public function tierRange(int $i): array
    {
        $min = $i === 0 ? 1 : $this->people[$i - 1]['max'] + 1;

        return [$min, $this->people[$i]['max']];
    }

    public function peopleLabel(int $i): string
    {
        [$min, $max] = $this->tierRange($i);

        return $min === $max
            ? trans_choice('app.pricing.people_exact', $max, ['count' => $max])
            : __('app.pricing.people_range', ['min' => $min, 'max' => $max]);
    }

    public function isFullDay(int $col): bool
    {
        return ! empty($this->durations[$col]['full_day']);
    }

    public function durationLabel(int $col): string
    {
        if ($this->isFullDay($col)) {
            return __('app.pricing.full_day');
        }

        return self::minutesLabel((int) $this->durations[$col]['minutes']);
    }

    /** "45 min", "1 hour", "2.5 hours" — shared by labels and notes. */
    public static function minutesLabel(int $minutes): string
    {
        if ($minutes < 60) {
            return __('app.pricing.minutes', ['count' => $minutes]);
        }
        $hours = rtrim(rtrim(number_format($minutes / 60, 2), '0'), '.');

        return trans_choice('app.pricing.hours', $minutes === 60 ? 1 : 2, ['count' => $hours]);
    }

    public function lowestPrice(): float
    {
        $all = array_merge(...array_map('array_values', $this->prices ?: [[0]]));

        return (float) min($all);
    }
}
