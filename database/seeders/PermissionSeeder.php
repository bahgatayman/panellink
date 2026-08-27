<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 'name' and 'group' are seed-time fallbacks only — the staff UI
        // always renders via app.permission.<key> / app.permission_group.<group>
        // (see resources/views/staff/_permission-grid.blade.php), so 'group'
        // is a stable slug here, not a display label.
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
            Permission::updateOrCreate(
                ['key' => $permission['key']],
                $permission + ['is_active' => true]
            );
        }
    }
}
