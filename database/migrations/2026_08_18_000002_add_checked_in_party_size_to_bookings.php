<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The actual headcount recorded at check-in, for shared-room advance
 * bookings (Phase 5). Deliberately a second column, not an overwrite of
 * party_size: party_size stays the original reservation size so a partial
 * no-show (reserved 3, 2 showed up) is analyzable later as reserved vs.
 * actual, rather than losing the original figure. Null until check-in
 * happens; never applicable to exclusive-room bookings.
 *
 * A plain nullable column addition — safe on both MySQL and SQLite with no
 * table rebuild, unlike the status column in the previous migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('checked_in_party_size')->nullable()->after('party_size');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('checked_in_party_size');
        });
    }
};
