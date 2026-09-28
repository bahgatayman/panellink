<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flexible room pricing (RoomPricingService is the only reader).
     *
     * rooms.pricing_model defaults to 'hourly' — every existing room keeps
     * pricing exactly as before (hours × price_per_hour, plus the shared-room
     * billing_unit rules), with no backfill. pricing_rules holds the owner's
     * own duration options / people tiers / price grid for the other models.
     *
     * shared_sessions.pricing_snapshot freezes those rules at session open,
     * the same reason billing_unit/billed_price_per_hour are snapshotted: an
     * owner editing prices mid-session must never change that session's bill.
     * Null = the legacy hourly path, bit-for-bit.
     *
     * bookings.guest_count is the people count a booking was priced for.
     * Deliberately separate from party_size, which stays "seats consumed"
     * (always 1 for an exclusive room) so availability math is untouched.
     * bookings.pricing_note is the human breakdown ("3-hour package · 2 people").
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('pricing_model', 20)->default('hourly')->after('billing_unit');
            $table->json('pricing_rules')->nullable()->after('pricing_model');
        });

        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->json('pricing_snapshot')->nullable()->after('billed_price_per_hour');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('guest_count')->nullable()->after('party_size');
            $table->string('pricing_note')->nullable()->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['pricing_model', 'pricing_rules']);
        });

        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->dropColumn('pricing_snapshot');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['guest_count', 'pricing_note']);
        });
    }
};
