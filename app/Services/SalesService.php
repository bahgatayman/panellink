<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SharedSession;

/**
 * Sales math + line-item management. Totals are always computed server-side
 * from the persisted line items — never trusted from the client.
 */
class SalesService
{
    /** The sale attached to a booking, creating it on first use. */
    public function saleForBooking(Booking $booking): Sale
    {
        return $booking->sale ?? Sale::create([
            'owner_id' => $booking->owner_id,
            'booking_id' => $booking->id,
            'hotspot_user_id' => $booking->hotspot_user_id,
            'status' => 'completed',
            'sold_at' => now(),
        ]);
    }

    /**
     * The open "tab" sale attached to a shared session, creating it on first use.
     * Stays status=open (and unpriced) until the session is closed.
     */
    public function saleForSharedSession(SharedSession $session): Sale
    {
        return $session->sale ?? Sale::create([
            'owner_id' => $session->owner_id,
            'shared_session_id' => $session->id,
            'hotspot_user_id' => $session->hotspot_user_id,
            'status' => 'open',
        ]);
    }

    /**
     * Move an open session's tab onto the booking created at close: keep the same
     * line items, drop the session link, and realize the sale as completed.
     */
    public function transferToBooking(Sale $sale, Booking $booking): void
    {
        $sale->update([
            'booking_id' => $booking->id,
            'shared_session_id' => null,
            'status' => 'completed',
            'sold_at' => now(),
        ]);
    }

    /**
     * Add a product to a sale as a snapshotted line item, then recompute totals.
     * name/unit_price/line_total are frozen here so later catalog edits never
     * rewrite this sale.
     */
    public function addItem(Sale $sale, Product $product, int $quantity): SaleItem
    {
        $quantity = max(1, $quantity);

        $item = $sale->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => $quantity,
            'line_total' => round((float) $product->price * $quantity, 2),
        ]);

        $this->recalculate($sale);

        return $item;
    }

    /** Remove a line item and recompute totals. */
    public function removeItem(SaleItem $item): void
    {
        $sale = $item->sale;
        $item->delete();
        $this->recalculate($sale);
    }

    /** Recompute subtotal/total from the line items and persist. */
    public function recalculate(Sale $sale): void
    {
        $subtotal = (float) $sale->items()->sum('line_total');

        $sale->update([
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal - (float) $sale->discount_total + (float) $sale->tax_total, 2),
        ]);
    }
}
