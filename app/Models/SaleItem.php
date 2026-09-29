<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'name',
        'unit_price',
        'unit_cost',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'quantity' => 'integer',
            'line_total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Cost of this line at the moment of sale (null when sold before costs were recorded). */
    public function lineCost(): ?float
    {
        return $this->unit_cost === null ? null : round((float) $this->unit_cost * $this->quantity, 2);
    }

    /** Gross profit of this line from its own snapshot, never the product's current cost. */
    public function lineProfit(): ?float
    {
        $cost = $this->lineCost();

        return $cost === null ? null : round((float) $this->line_total - $cost, 2);
    }
}
