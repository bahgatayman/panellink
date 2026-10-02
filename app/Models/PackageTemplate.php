<?php

namespace App\Models;

use App\Support\Duration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An owner's reusable hour-package offer ("30 Hours Monthly"). Assigning it
 * to a member snapshots every term onto a MemberPackage — editing a template
 * never changes packages already sold.
 */
class PackageTemplate extends Model
{
    protected $fillable = ['owner_id', 'name', 'total_minutes', 'price', 'validity_days', 'room_ids', 'is_active'];

    protected function casts(): array
    {
        return [
            'total_minutes' => 'integer',
            'price' => 'decimal:2',
            'validity_days' => 'integer',
            'room_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function memberPackages(): HasMany
    {
        return $this->hasMany(MemberPackage::class);
    }

    public function hoursLabel(): string
    {
        return Duration::label($this->total_minutes);
    }
}
