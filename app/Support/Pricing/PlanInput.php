<?php

namespace App\Support\Pricing;

/**
 * Parses + validates the room form's Custom Plans payload (hidden JSON input).
 * Pure data, no persistence and no money math — RoomController syncs the
 * result, RoomPricingService prices it.
 *
 * Each plan: {id?, name?, people, minutes?|full_day, price}
 */
final class PlanInput
{
    public const MAX_PLANS = 20;

    /**
     * @return array{0: array<int, array{id: ?int, name: ?string, people: int, duration_minutes: ?int, is_full_day: bool, price: float}>, 1: array<int, string>}
     */
    public static function parse(mixed $raw, string $roomType, int $capacity): array
    {
        if ($raw === null || $raw === '') {
            return [[], []];
        }
        $data = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($data)) {
            return [[], [__('app.plans.errors.invalid')]];
        }
        if (count($data) > self::MAX_PLANS) {
            return [[], [__('app.plans.errors.too_many', ['max' => self::MAX_PLANS])]];
        }

        $plans = [];
        $errors = [];
        $seen = [];
        foreach (array_values($data) as $p) {
            if (! is_array($p)) {
                $errors[] = __('app.plans.errors.invalid');

                continue;
            }
            $name = trim((string) ($p['name'] ?? '')) ?: null;
            if ($name !== null && mb_strlen($name) > 80) {
                $errors[] = __('app.plans.errors.name_long');
            }

            $people = filter_var($p['people'] ?? null, FILTER_VALIDATE_INT);
            if ($people === false || $people < 1 || $people > 999) {
                $errors[] = __('app.plans.errors.people_range');
            } elseif ($roomType === 'shared' && $people > $capacity) {
                // Only a shared room's capacity is a real seat limit (Room::effectiveCapacity()).
                $errors[] = __('app.plans.errors.over_capacity', ['capacity' => $capacity]);
            }

            $fullDay = ! empty($p['full_day']);
            $minutes = $fullDay ? null : filter_var($p['minutes'] ?? null, FILTER_VALIDATE_INT);
            if (! $fullDay && ($minutes === false || $minutes < PricingRules::MIN_DURATION_MINUTES || $minutes > PricingRules::MAX_DURATION_MINUTES)) {
                $errors[] = __('app.plans.errors.duration_range');
            }

            $price = $p['price'] ?? null;
            if ($price === null || $price === '' || ! is_numeric($price) || (float) $price < 0 || (float) $price > 1_000_000) {
                $errors[] = __('app.plans.errors.price_missing');
            }

            $key = $people.'|'.($fullDay ? 'fd' : $minutes);
            if (isset($seen[$key])) {
                $errors[] = __('app.plans.errors.duplicate');
            }
            $seen[$key] = true;

            $id = filter_var($p['id'] ?? null, FILTER_VALIDATE_INT);
            $plans[] = [
                'id' => $id ?: null,
                'name' => $name,
                'people' => (int) $people,
                'duration_minutes' => $fullDay ? null : (int) $minutes,
                'is_full_day' => $fullDay,
                'price' => round((float) $price, 2),
            ];
        }

        $errors = array_values(array_unique($errors));

        return [$errors ? [] : $plans, $errors];
    }
}
