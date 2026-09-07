<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How a shared room's session time is billed. 'minute' (default) is
     * today's existing continuous per-minute formula, bit-for-bit unchanged
     * — an existing owner's bill never silently changes. 'half_hour'/'hour'
     * charge for every started block, rounded up (SharedSessionBillingService).
     * Only meaningful for type='shared' today; ignored for other room types,
     * same as how 'capacity' already exists on every room but is only
     * enforced for shared rooms via Room::effectiveCapacity().
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('billing_unit')->default('minute')->after('price_per_hour');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('billing_unit');
        });
    }
};
