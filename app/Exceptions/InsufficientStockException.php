<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

/** Thrown by InventoryService when a sale or reduction asks for more units than are in stock. */
class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly Product $product, public readonly int $available)
    {
        parent::__construct(trans_choice('app.inventory.errors.insufficient', $available, [
            'count' => $available,
            'name' => $product->name,
        ]));
    }
}
