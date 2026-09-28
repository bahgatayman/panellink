<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->default(0)->after('total_price');
            $table->string('payment_status', 16)->default('unpaid')->after('amount_paid');
        });

        // Backfill so RevenueAnalyticsService's switch from total_price to
        // amount_paid doesn't silently zero out every historical figure.
        // Only 'completed' bookings are ever summed as revenue, so that's
        // the only status that needs to look "fully paid" retroactively —
        // pending/confirmed/checked_in/cancelled/no_show stay at the new
        // unpaid defaults, since we have no real record of what (if
        // anything) was actually collected on those.
        DB::table('bookings')
            ->where('status', 'completed')
            ->update([
                'amount_paid' => DB::raw('total_price'),
                'payment_status' => 'paid',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'payment_status']);
        });
    }
};
