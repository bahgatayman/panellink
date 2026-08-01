<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'type',
        'price',
        'sku',
        'track_stock',
        'stock_quantity',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'track_stock' => 'boolean',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'service' => 'Service',
            default => 'Product',
        };
    }

    public function typeColor(): string
    {
        return match ($this->type) {
            'service' => 'purple',
            default => 'blue',
        };
    }
}
