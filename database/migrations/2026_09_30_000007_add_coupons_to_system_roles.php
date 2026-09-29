<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Grants coupons.apply to receptionist (inherited by staff/manager via
     * RoleSeeder's array_merge bundling) and the other four coupons.* keys
     * to manager only, on any environment that only ever runs
     * `php artisan migrate` — same reasoning as
     * 2026_09_29_000006_add_expenses_to_manager_role. Must run after
     * 2026_09_30_000006 so the permission rows already exist to look up.
     */
    public function up(): void
    {
        $now = now();

        $grants = [
            'receptionist' => ['coupons.apply'],
            'manager' => ['coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete', 'coupons.apply'],
        ];

        foreach ($grants as $roleKey => $permissionKeys) {
            $roleId = DB::table('roles')->where('owner_id', null)->where('key', $roleKey)->value('id');

            if (! $roleId) {
                continue; // role not seeded yet on this environment — nothing to attach to
            }

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
