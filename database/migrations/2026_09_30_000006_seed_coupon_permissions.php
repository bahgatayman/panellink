<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Same production-safety pattern as 2026_09_29_000005_seed_expense_permissions:
     * production only ever runs `php artisan migrate`, never `db:seed`, so any
     * permission that should exist there must also be inserted by a migration.
     * PermissionSeeder stays the source of truth for local/test `db:seed`.
     */
    public function up(): void
    {
        $now = now();

        $permissions = [
            ['key' => 'coupons.view', 'name' => 'View Coupons', 'group' => 'coupons'],
            ['key' => 'coupons.create', 'name' => 'Create Coupons', 'group' => 'coupons'],
            ['key' => 'coupons.edit', 'name' => 'Edit Coupons', 'group' => 'coupons'],
            ['key' => 'coupons.delete', 'name' => 'Delete Coupons', 'group' => 'coupons'],
            ['key' => 'coupons.apply', 'name' => 'Apply Coupons at Checkout', 'group' => 'coupons'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $permission['key']],
                $permission + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
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
