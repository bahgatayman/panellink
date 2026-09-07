<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Captured from the room at SharedSessionController::store() (session
     * open) time, not read live from the room at close time. Without this,
     * an Owner changing a room's billing_unit or price_per_hour while a
     * session is already open would silently change that session's final
     * bill — this closes that gap for both fields at once. Nullable: rows
     * that predate these columns are already-closed immutable history and
     * need no backfill.
     */
    public function up(): void
    {
        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->string('billing_unit')->nullable()->after('booking_id');
            $table->decimal('billed_price_per_hour', 8, 2)->nullable()->after('billing_unit');
        });
    }

    public function down(): void
    {
        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->dropColumn(['billing_unit', 'billed_price_per_hour']);
        });
    }
};
