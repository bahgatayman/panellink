<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The three system roles (and their permission bundles), guaranteed
     * present on any environment that has run `php artisan migrate` — the
     * same reasoning as 2026_09_29_000001_seed_full_permission_catalog:
     * RoleSeeder (and its role->permissions sync) only ever ran via
     * `db:seed`, which this app's deploy path never calls. An environment
     * that only ever ran `migrate` therefore has an EMPTY `roles` table —
     * worse than the permissions gap alone, since it means the Staff form's
     * Role dropdown has nothing in it and no staff member can ever be
     * assigned a system role, only "No role".
     *
     * Must run after 2026_09_29_000001 (same-day, later sequence number) so
     * every permission key below already exists to look up.
     *
     * Keyed by (owner_id=null, key) for roles and (role_id, permission_id)
     * for the pivot — both already unique-constrained — so this is safe on
     * a fresh install, an already-seeded install, or (like production
     * right now) an install with some but not all of this data.
     * RoleSeeder stays the source of truth for local/test `db:seed`; this
     * migration is the same safety net for migrate-only environments.
     */
    public function up(): void
    {
        $now = now();

        $receptionist = [
            'members.view', 'members.create', 'members.edit',
            'bookings.view', 'bookings.create', 'bookings.edit',
            'shared_sessions.view', 'shared_sessions.manage',
            'workspaces.view',
        ];

        $staff = array_merge($receptionist, [
            'products.view',
            'bookings.cancel',
        ]);

        $manager = array_merge($staff, [
            'products.manage', 'sales.view', 'reports.view',
            'financials.view', 'financials.export',
            'members.delete', 'members.manage_status',
            'settings.view',
        ]);

        $roles = [
            ['key' => 'receptionist', 'name' => 'Receptionist', 'description' => 'Front-desk: members and bookings.', 'permissions' => $receptionist],
            ['key' => 'staff', 'name' => 'Staff', 'description' => 'Receptionist plus product sales and booking cancellation.', 'permissions' => $staff],
            ['key' => 'manager', 'name' => 'Manager', 'description' => 'Full operational access, including sales and reports.', 'permissions' => $manager],
        ];

        foreach ($roles as $definition) {
            $roleId = DB::table('roles')->where('owner_id', null)->where('key', $definition['key'])->value('id');

            if ($roleId) {
                DB::table('roles')->where('id', $roleId)->update([
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_system' => true,
                    'updated_at' => $now,
                ]);
            } else {
                $roleId = DB::table('roles')->insertGetId([
                    'owner_id' => null,
                    'key' => $definition['key'],
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $permissionIds = DB::table('permissions')->whereIn('key', $definition['permissions'])->pluck('id', 'key');

            foreach ($definition['permissions'] as $key) {
                $permissionId = $permissionIds[$key] ?? null;

                if (! $permissionId) {
                    continue; // shouldn't happen once 2026_09_29_000001 has run, but never worth failing the deploy over
                }

                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    /**
     * Intentionally a no-op: rolling back must never delete roles that
     * staff rows may already reference, or the permission grants that go
     * with them.
     */
    public function down(): void
    {
        //
    }
};
