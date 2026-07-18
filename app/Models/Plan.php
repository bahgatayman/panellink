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
            'sort_order'      => 'integer',
        ];
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
