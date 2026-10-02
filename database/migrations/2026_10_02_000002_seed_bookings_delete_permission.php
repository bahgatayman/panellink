<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Same production-safety pattern as 2026_09_29_000005_seed_expense_permissions:
     * production only ever runs `php artisan migrate`, never `db:seed`, so this
     * new Delete Booking permission must also be inserted by a migration.
     * PermissionSeeder stays the source of truth for local/test `db:seed`.
     */
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['key' => 'bookings.delete'],
            ['name' => 'Delete Bookings', 'group' => 'bookings', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
        );
    }

    /**
     * Intentionally a no-op: rolling back must never delete permission rows
     * that role/staff pivots may already reference.
     */
    public function down(): void
    {
        //
    }
};
