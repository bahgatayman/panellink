<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hour Packages permissions, inserted by a migration because production
     * only runs `php artisan migrate` (same pattern as the coupon/expense
     * permission migrations; PermissionSeeder/RoleSeeder mirror these).
     *
     * packages.view   — see package templates
     * packages.manage — create/edit/toggle/delete templates
     * packages.assign — give a member a package, or cancel one
     * Using a package inside a booking needs only bookings.create/edit.
     */
    public function up(): void
    {
        $now = now();

        $permissions = [
            ['key' => 'packages.view', 'name' => 'View Hour Packages', 'group' => 'packages'],
            ['key' => 'packages.manage', 'name' => 'Manage Hour Package Templates', 'group' => 'packages'],
            ['key' => 'packages.assign', 'name' => 'Assign & Cancel Member Packages', 'group' => 'packages'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $permission['key']],
                $permission + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        // Mirrors RoleSeeder's bundles (receptionist ⊂ staff ⊂ manager).
        $grants = [
            'receptionist' => ['packages.view', 'packages.assign'],
            'staff' => ['packages.view', 'packages.assign'],
            'manager' => ['packages.view', 'packages.manage', 'packages.assign'],
        ];

        foreach ($grants as $roleKey => $keys) {
            $roleId = DB::table('roles')->where('owner_id', null)->where('key', $roleKey)->value('id');
            if (! $roleId) {
                continue; // role not seeded yet on this environment
            }
            foreach (DB::table('permissions')->whereIn('key', $keys)->pluck('id') as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    /** No-op on purpose: never delete permission rows that pivots may reference. */
    public function down(): void
    {
        //
    }
};
