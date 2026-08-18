<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One day-of-week's schedule for an owner. Deliberately holds no behavior
 * of its own (no "crosses midnight," no "effective window" logic) — that
 * math lives entirely in BusinessHoursService, so there is exactly one
 * place it can be found or drift from.
 */
class WorkingHour extends Model
{
    protected $fillable = [
        'owner_id',
        'day_of_week',
        'is_open',
        'open_time',
        'close_time',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'day_of_week' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}
