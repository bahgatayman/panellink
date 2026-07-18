<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->date('session_date')->nullable()->after('hotspot_user_id');
            $table->time('start_time')->nullable()->after('session_date');
            $table->foreignId('booking_id')
                  ->nullable()
                  ->after('status')
                  ->constrained('bookings')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn(['session_date', 'start_time', 'booking_id']);
        });
    }
};
