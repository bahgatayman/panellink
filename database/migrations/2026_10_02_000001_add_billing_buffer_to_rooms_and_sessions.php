<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shared-room Billing Buffer (grace period): minutes a customer may stay
     * past a billing-block boundary before the next block is charged. Lives
     * next to billing_unit on the room (the existing shared-billing policy
     * level) and is snapshotted onto each session when it opens, exactly like
     * billing_unit/billed_price_per_hour — so changing it never re-prices a
     * session that's already running.
     *
     * Default 0 on rooms, NULL (= 0) on existing sessions: every tenant keeps
     * today's billing bit-for-bit until they configure a buffer.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedSmallInteger('billing_buffer_minutes')->default(0)->after('billing_unit');
        });

        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('billing_buffer_minutes')->nullable()->after('billing_unit');
        });
    }

    public function down(): void
    {
        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->dropColumn('billing_buffer_minutes');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('billing_buffer_minutes');
        });
    }
};
