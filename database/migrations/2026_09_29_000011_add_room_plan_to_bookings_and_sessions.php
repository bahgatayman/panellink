<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * bookings.room_plan_id — which Custom Plan a booking was sold on. Null on
     * delete: the booking's own total_price/pricing_note already record what
     * was charged, so editing or removing a plan never rewrites history.
     *
     * shared_sessions.plan_snapshot — {name, minutes, price} frozen at check-in
     * of a plan reservation, so the session's bill is the plan price plus any
     * time beyond the plan at the room's standard pricing.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('room_plan_id')->nullable()->after('room_id')->constrained('room_plans')->nullOnDelete();
        });

        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->json('plan_snapshot')->nullable()->after('pricing_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_plan_id');
        });

        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->dropColumn('plan_snapshot');
        });
    }
};
