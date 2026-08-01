<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'max_members',
        'features',
        'max_workspaces',
        'max_rooms',
        'max_products',
        'price_per_month',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'       => 'boolean',
            'price_per_month' => 'decimal:2',
            'max_members'     => 'integer',
            'features'        => 'array',
            'max_workspaces'  => 'integer',
            'max_rooms'       => 'integer',
            'max_products'    => 'integer',
            'sort_order'      => 'integer',
        ];
    }

    /** Feature keys enabled by default for owners on this plan. */
    public function defaultFeatures(): array
    {
        return $this->features ?? [];
    }

    public function hasFeature(string $key): bool
    {
        return in_array($key, $this->features ?? [], true);
    }

    public function isFree(): bool
    {
        return $this->price_per_month == 0;
    }

    public function formattedPrice(): string
    {
        if ($this->isFree()) return 'Free';
        return 'ج.م ' . number_format($this->price_per_month, 0) . ' / month';
    }

    public function owners(): HasMany
    {
        return $this->hasMany(Owner::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
