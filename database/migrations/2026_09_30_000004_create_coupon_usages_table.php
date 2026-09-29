<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Full redemption history (never just a counter). `unique('booking_id')`
     * is the real idempotency backstop against double-recording the same
     * transaction — a DB-level lock alone can't be trusted here since SQLite
     * (used in tests) never honors lockForUpdate(), matching the rest of
     * this codebase's "lock, write, re-check" convention.
     */
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('hotspot_user_id')->nullable()->constrained('hotspot_users')->nullOnDelete();
            $table->decimal('original_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('final_amount', 10, 2);
            $table->decimal('room_discount', 10, 2)->default(0);
            $table->decimal('product_discount', 10, 2)->default(0);
            $table->dateTime('used_at');
            $table->timestamps();

            $table->unique('booking_id');
            $table->index(['coupon_id', 'hotspot_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
