<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Grants the 5 new expenses.* permissions (seeded by the previous
     * migration) to the system 'manager' role, on any environment that only
     * ever runs `php artisan migrate` — same reasoning as
     * 2026_09_29_000002_seed_system_roles_and_role_permissions. Must run
     * after 2026_09_29_000005 so the permission rows already exist to look up.
     */
    public function up(): void
    {
        $now = now();

        $roleId = DB::table('roles')->where('owner_id', null)->where('key', 'manager')->value('id');

        if (! $roleId) {
            return; // manager role not seeded yet on this environment — nothing to attach to
        }

        $permissionKeys = [
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete', 'expenses.manage_categories',
        ];

        $permissionIds = DB::table('permissions')->whereIn('key', $permissionKeys)->pluck('id', 'key');

        foreach ($permissionKeys as $key) {
            $permissionId = $permissionIds[$key] ?? null;

            if (! $permissionId) {
                continue;
            }

            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    /**
     * Intentionally a no-op: rolling back must never revoke a permission
     * grant that may have been explicitly relied upon.
     */
    public function down(): void
    {
        //
    }
};
