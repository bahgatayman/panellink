<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The full permission catalog, guaranteed present on any environment
     * that has run `php artisan migrate` — regardless of whether `db:seed`
     * was ever run.
     *
     * Until now, 23 of the 25 rows in `permissions` only ever existed via
     * PermissionSeeder, a seeder that this app's `composer.json` setup
     * scripts never call. Only the 2 `financials.*` rows were inserted by
     * an actual migration (2026_08_20_000002_add_financials_permissions),
     * which runs unconditionally. Any environment provisioned without an
     * explicit `db:seed` step (this project's own deploy path is exactly
     * that: migrate-only, no seeding) ends up with a `permissions` table
     * containing only those 2 rows — which reproduces the reported bug
     * one-for-one: the Staff → Add/Edit Staff permissions grid faithfully
     * renders every row that exists, so with only 2 rows present it shows
     * only "Financials · Export Financial Reports, View Financials" and
     * nothing else. The Blade/JS in that form was never broken — the data
     * underneath it was incomplete.
     *
     * `updateOrInsert` keyed on `key` makes this self-healing and safe to
     * run on every environment (dev, already-seeded, or previously
     * migrate-only) without creating duplicates or clobbering an existing
     * row's id — PermissionSeeder stays the source of truth for local/test
     * setup (`php artisan db:seed`), this migration is the safety net that
     * guarantees the same 25 rows exist wherever `migrate` alone is run.
     */
    public function up(): void
    {
        $now = now();

        $permissions = [
            ['key' => 'members.view', 'name' => 'View Members', 'group' => 'members'],
            ['key' => 'members.create', 'name' => 'Add Members', 'group' => 'members'],
            ['key' => 'members.edit', 'name' => 'Edit Members', 'group' => 'members'],
            ['key' => 'members.delete', 'name' => 'Delete Members', 'group' => 'members'],
            ['key' => 'members.manage_status', 'name' => 'Enable/Disable Members', 'group' => 'members'],

            ['key' => 'hotspot.manage_speed', 'name' => 'Manage Hotspot Speed Profiles', 'group' => 'hotspot'],
            ['key' => 'hotspot.view_sessions', 'name' => 'View Live Hotspot Sessions', 'group' => 'hotspot'],

            ['key' => 'workspaces.view', 'name' => 'View Workspaces & Rooms', 'group' => 'workspaces'],
            ['key' => 'workspaces.manage', 'name' => 'Manage Workspaces & Rooms', 'group' => 'workspaces'],

            ['key' => 'shared_sessions.view', 'name' => 'View Shared Sessions', 'group' => 'shared_sessions'],
            ['key' => 'shared_sessions.manage', 'name' => 'Open/Close Shared Sessions', 'group' => 'shared_sessions'],

            ['key' => 'bookings.view', 'name' => 'View Bookings', 'group' => 'bookings'],
            ['key' => 'bookings.create', 'name' => 'Create Bookings', 'group' => 'bookings'],
            ['key' => 'bookings.edit', 'name' => 'Edit Bookings', 'group' => 'bookings'],
            ['key' => 'bookings.cancel', 'name' => 'Cancel Bookings', 'group' => 'bookings'],

            ['key' => 'products.view', 'name' => 'View Products', 'group' => 'products_sales'],
            ['key' => 'products.manage', 'name' => 'Manage Products', 'group' => 'products_sales'],
            ['key' => 'sales.view', 'name' => 'View Sales', 'group' => 'products_sales'],

            ['key' => 'reports.view', 'name' => 'View Reports & Revenue', 'group' => 'reports'],

            ['key' => 'financials.view', 'name' => 'View Financials', 'group' => 'financials'],
            ['key' => 'financials.export', 'name' => 'Export Financial Reports', 'group' => 'financials'],

            ['key' => 'settings.view', 'name' => 'View Settings', 'group' => 'settings'],
            ['key' => 'settings.manage', 'name' => 'Manage Settings', 'group' => 'settings'],

            ['key' => 'staff.view', 'name' => 'View Staff', 'group' => 'staff'],
            ['key' => 'staff.manage', 'name' => 'Manage Staff', 'group' => 'staff'],
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
     * that role/staff pivots may already reference (and the pre-existing
     * 2026_08_20_000002 migration already owns deleting the financials.*
     * pair on its own rollback).
     */
    public function down(): void
    {
        //
    }
};
