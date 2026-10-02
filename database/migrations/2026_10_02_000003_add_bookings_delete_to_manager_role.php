<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Grants the new bookings.delete permission (seeded by the previous
     * migration) to the system 'manager' role, on any environment that only
     * ever runs `php artisan migrate` — same reasoning as
     * 2026_09_29_000006_add_expenses_to_manager_role. Must run after
     * 2026_10_02_000002 so the permission row already exists to look up.
     * Manager-only, not staff/receptionist: a step above bookings.cancel.
     */
    public function up(): void
    {
        $now = now();

        $roleId = DB::table('roles')->where('owner_id', null)->where('key', 'manager')->value('id');

        if (! $roleId) {
            return; // manager role not seeded yet on this environment — nothing to attach to
        }

        $permissionId = DB::table('permissions')->where('key', 'bookings.delete')->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('role_permissions')->updateOrInsert(
            ['role_id' => $roleId, 'permission_id' => $permissionId],
            ['created_at' => $now, 'updated_at' => $now]
        );
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
