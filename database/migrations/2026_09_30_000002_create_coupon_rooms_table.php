<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zero rows for a given coupon = that coupon applies to ALL rooms — a
     * derived meaning, not a stored flag (see Coupon::targetsAllRooms()).
     */
    public function up(): void
    {
        Schema::create('coupon_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['coupon_id', 'room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_rooms');
    }
};
