<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * shared_sessions.booking_id already carries a FK constraint (added in
     * 2026_07_02_000001), but no explicit index — MySQL/InnoDB auto-creates
     * one to support the FK, SQLite does not. The Financials module's
     * Booking::sharedSession() eager-load filters on this column on every
     * Transactions page, so it needs a real index rather than relying on
     * engine-specific implicit behavior.
     */
    public function up(): void
    {
        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->dropIndex(['booking_id']);
        });
    }
};
