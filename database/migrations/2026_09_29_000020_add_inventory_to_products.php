<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inventory & product cost. `price` stays the selling price and the
     * existing `track_stock`/`stock_quantity` columns become live (they were
     * created for this and never used). Only InventoryService changes stock.
     *
     * products.purchase_price      — current cost per unit.
     * products.low_stock_threshold — alert when stock reaches this (null = no alert).
     * products.stock_alert         — last alert sent in the current cycle ('low'|'out'),
     *                                only to avoid repeating it; the stock status itself
     *                                is always derived from stock_quantity/threshold.
     * sale_items.unit_cost         — cost snapshot at the moment of sale, so a later
     *                                purchase-price change never rewrites historical
     *                                profit. Null for rows sold before this existed.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_price', 10, 2)->default(0)->after('price');
            $table->unsignedInteger('low_stock_threshold')->nullable()->after('stock_quantity');
            $table->string('stock_alert', 8)->nullable()->after('low_stock_threshold');
        });

        // stock_quantity was nullable and unused; give every row a real count.
        DB::table('products')->whereNull('stock_quantity')->update(['stock_quantity' => 0]);

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['purchase_price', 'low_stock_threshold', 'stock_alert']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
