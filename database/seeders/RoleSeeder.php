<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
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
            $role = Role::updateOrCreate(
                ['owner_id' => null, 'key' => $definition['key']],
                ['name' => $definition['name'], 'description' => $definition['description'], 'is_system' => true]
            );

            $permissionIds = Permission::whereIn('key', $definition['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
