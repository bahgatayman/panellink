<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->string('name');
            // 'product' = stocked good (coffee, snacks); 'service' = unlimited (printing, rental).
            $table->enum('type', ['product', 'service'])->default('product');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('sku')->nullable();
            // Inventory columns exist now but are not enforced until the stock phase.
            $table->boolean('track_stock')->default(false);
            $table->integer('stock_quantity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
