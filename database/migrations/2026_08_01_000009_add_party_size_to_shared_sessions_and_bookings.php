<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Default 1 reproduces today's behavior exactly for every existing row —
        // one session/booking has always meant one person until now.
        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->unsignedInteger('party_size')->default(1)->after('hotspot_user_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('party_size')->default(1)->after('hotspot_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->dropColumn('party_size');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('party_size');
        });
    }
};
