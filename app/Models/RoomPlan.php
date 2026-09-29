<?php

namespace App\Models;

use App\Support\Pricing\PricingRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Custom Plan: a fixed-price package for one room, for exactly `people`
 * people over `duration_minutes` (or a Full Day). Priced only through
 * RoomPricingService::quotePlan() — this model holds data and labels, no money math.
 */
class RoomPlan extends Model
{
    protected $fillable = [
        'owner_id', 'room_id', 'name', 'people', 'duration_minutes', 'is_full_day', 'price', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'people' => 'integer',
            'duration_minutes' => 'integer',
            'is_full_day' => 'boolean',
            'price' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** "5 hours", "90 min", "Full day". */
    public function durationLabel(): string
    {
        return $this->is_full_day
            ? __('app.pricing.full_day')
            : PricingRules::minutesLabel((int) $this->duration_minutes);
    }

    /** "10 people" / "1 person". */
    public function peopleLabel(): string
    {
        return trans_choice('app.pricing.people_exact', $this->people, ['count' => $this->people]);
    }

    /** The owner's name for it, or a sensible default ("10 people · 5 hours"). */
    public function displayName(): string
    {
        return $this->name ?: $this->peopleLabel().' · '.$this->durationLabel();
    }

    /** Stored on the booking/session: "Team Package · 10 people · 5 hours". */
    public function note(): string
    {
        return $this->name
            ? $this->name.' · '.$this->peopleLabel().' · '.$this->durationLabel()
            : $this->peopleLabel().' · '.$this->durationLabel();
    }
}
