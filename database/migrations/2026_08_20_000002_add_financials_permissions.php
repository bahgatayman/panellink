<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * New gates for the Financials module (replaces the Sales sidebar
     * section). Deliberately not reusing reports.view/sales.view as the
     * primary gate — see RevenueAnalyticsService/FinancialController design
     * notes: CheckPermission only supports OR across a comma-separated key
     * list, so there's no clean way to require "both reports.view AND
     * sales.view" as one gate, and plain OR would over-grant to staff who
     * only ever held the narrower sales.view. Existing staff are NOT
     * auto-granted these — Owners opt staff in manually via Staff Accounts,
     * matching this table's existing insert-only-never-auto-pivot precedent.
     */
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->insert([
            [
                'key' => 'financials.view',
                'name' => 'View Financials',
                'group' => 'financials',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'financials.export',
                'name' => 'Export Financial Reports',
                'group' => 'financials',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('key', ['financials.view', 'financials.export'])->delete();
    }
};
