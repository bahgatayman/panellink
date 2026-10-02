<?php

namespace App\Support\Pricing;

/**
 * Parses + validates the room form's Pricing Profiles (a JSON list from the
 * profiles editor), same approach as PlanInput: the browser only builds the
 * list, every rule is enforced here.
 */
final class PricingProfileInput
{
    public const MAX_PROFILES = 20;

    public const MAX_PRICE = 999999.99;

    /**
     * @return array{0: array<int, array{id: ?int, name: string, price_per_hour: float, is_active: bool}>, 1: array<int, string>}
     */
    public static function parse(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [[], []];
        }
        $data = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($data)) {
            return [[], [__('app.pricing_profiles.errors.invalid')]];
        }
        if (count($data) > self::MAX_PROFILES) {
            return [[], [__('app.pricing_profiles.errors.too_many', ['max' => self::MAX_PROFILES])]];
        }

        $profiles = [];
        $errors = [];
        $activeNames = [];
        foreach (array_values($data) as $p) {
            if (! is_array($p)) {
                $errors[] = __('app.pricing_profiles.errors.invalid');

                continue;
            }

            $name = trim((string) ($p['name'] ?? ''));
            if ($name === '') {
                $errors[] = __('app.pricing_profiles.errors.name_required');
            } elseif (mb_strlen($name) > 60) {
                $errors[] = __('app.pricing_profiles.errors.name_long');
            }

            $price = filter_var($p['price_per_hour'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($price === false || $price < 0 || $price > self::MAX_PRICE) {
                $errors[] = __('app.pricing_profiles.errors.price');
            }

            $active = filter_var($p['is_active'] ?? true, FILTER_VALIDATE_BOOL);
            if ($active && $name !== '') {
                $key = mb_strtolower($name);
                if (isset($activeNames[$key])) {
                    $errors[] = __('app.pricing_profiles.errors.duplicate', ['name' => $name]);
                }
                $activeNames[$key] = true;
            }

            $id = filter_var($p['id'] ?? null, FILTER_VALIDATE_INT);
            $profiles[] = [
                'id' => $id === false ? null : $id,
                'name' => $name,
                'price_per_hour' => $price === false ? 0.0 : round((float) $price, 2),
                'is_active' => $active,
            ];
        }

        return [$profiles, array_values(array_unique($errors))];
    }
}
