<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * bookings.total_price stays the gross room charge exactly as
     * RoomPricingService returns it — never rewritten. discount_total is the
     * room-side coupon discount on top of it; Booking::netRoomCharge()
     * = total_price - discount_total is what payment/balance math now uses.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('room_plan_id')->constrained('coupons')->nullOnDelete();
            $table->decimal('discount_total', 10, 2)->default(0)->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn('discount_total');
        });
    }
};
