<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which pricing profile a booking/session was priced with. The rate itself
     * is already snapshotted (bookings.price_per_hour / total_price,
     * shared_sessions.billed_price_per_hour); the name is copied too so history
     * still reads "Photography" after the profile is renamed or deleted.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('room_pricing_profile_id')->nullable()->after('room_plan_id')
                ->constrained('room_pricing_profiles')->nullOnDelete();
            $table->string('pricing_profile_name', 60)->nullable()->after('room_pricing_profile_id');
        });

        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->foreignId('room_pricing_profile_id')->nullable()->after('room_id')
                ->constrained('room_pricing_profiles')->nullOnDelete();
            $table->string('pricing_profile_name', 60)->nullable()->after('room_pricing_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_pricing_profile_id');
            $table->dropColumn('pricing_profile_name');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_pricing_profile_id');
            $table->dropColumn('pricing_profile_name');
        });
    }
};
