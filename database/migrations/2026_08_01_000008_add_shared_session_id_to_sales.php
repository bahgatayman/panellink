<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // A sale is attached to EITHER an open shared session (running tab) OR a
            // booking. On session close the sale moves to the booking and this clears.
            $table->foreignId('shared_session_id')->nullable()->after('booking_id')
                ->constrained('shared_sessions')->nullOnDelete();
            $table->index('shared_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shared_session_id');
        });
    }
};
