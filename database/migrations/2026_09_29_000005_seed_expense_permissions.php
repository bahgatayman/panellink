<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Same production-safety pattern as 2026_09_29_000001_seed_full_permission_catalog:
     * production only ever runs `php artisan migrate`, never `db:seed`, so any
     * permission that should exist there must also be inserted by a migration.
     * PermissionSeeder stays the source of truth for local/test `db:seed`.
     */
    public function up(): void
    {
        $now = now();

        $permissions = [
            ['key' => 'expenses.view', 'name' => 'View Expenses', 'group' => 'expenses'],
            ['key' => 'expenses.create', 'name' => 'Add Expenses', 'group' => 'expenses'],
            ['key' => 'expenses.edit', 'name' => 'Edit Expenses', 'group' => 'expenses'],
            ['key' => 'expenses.delete', 'name' => 'Delete Expenses', 'group' => 'expenses'],
            ['key' => 'expenses.manage_categories', 'name' => 'Manage Expense Categories', 'group' => 'expenses'],
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
