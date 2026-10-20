<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['permission', 'role', 'user', 'employee', 'ticket', 'setting', 'wa'];
        $actions = ['create', 'read', 'update', 'delete', 'import', 'export'];

        $allPermissions = [];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permission = Permission::firstOrCreate(
                    ['name' => "$module.$action"],
                    [
                        'group' => $module,
                        'label' => ucfirst($action).' '.ucfirst($module),
                    ]
                );
                $allPermissions[] = $permission;
            }
        }

        // super-admin -> all permissions
        $role = Role::firstOrCreate(['name' => 'super-admin']);
        $role->syncPermissions($allPermissions);

        // admin -> all user.*, and every WA module permission
        $adminPerms = Permission::whereIn('group', ['user', 'employee', 'ticket', 'setting', 'wa'])
            ->pluck('name')->toArray();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->syncPermissions($adminPerms);

        // technician -> tickets + reading WhatsApp status only
        $technicianPerms = Permission::whereIn('name', [
            'ticket.read', 'ticket.update', 'wa.read',
        ])->pluck('name')->toArray();
        $role = Role::firstOrCreate(['name' => 'technician']);
        $role->syncPermissions($technicianPerms);

        // user -> user.create, user.read, user.import
        $limitedUserPerms = Permission::whereIn('name', [
            'user.create', 'user.read', 'user.import',
        ])->pluck('name')->toArray();
        $role = Role::firstOrCreate(['name' => 'user']);
        $role->syncPermissions($limitedUserPerms);
    }
}
