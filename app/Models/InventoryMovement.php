<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One change to a product's stock. Written only by InventoryService. */
class InventoryMovement extends Model
{
    public const INITIAL = 'initial_stock';

    public const SALE = 'sale';

    public const SALE_REMOVED = 'sale_removed';

    public const RESTOCK = 'restock';

    public const ADJUSTMENT = 'adjustment';

    /** Reasons accepted for a manual reduction. */
    public const REASONS = ['damaged', 'expired', 'lost', 'correction', 'other'];

    protected $fillable = [
        'owner_id', 'product_id', 'type', 'quantity_change', 'previous_quantity', 'new_quantity',
        'sale_id', 'sale_item_id', 'reason', 'note', 'created_by_type', 'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'previous_quantity' => 'integer',
            'new_quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    /** "Initial stock", "Sale", "Restock", … */
    public function label(): string
    {
        return __('app.inventory.types.'.$this->type);
    }

    /** Who made it: the staff member's or owner's name, when recorded. */
    public function actorName(): ?string
    {
        return match ($this->created_by_type) {
            'staff' => Staff::find($this->created_by_id)?->name,
            'owner' => Owner::find($this->created_by_id)?->name,
            default => null,
        };
    }
}
