<?php

namespace App\Imports;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RoleImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $role = Role::firstOrCreate(['name' => $row['name'] ?? '']);

        if (!empty($row['permissions'])) {
            $permNames = array_map('trim', explode(',', $row['permissions']));
            $permissions = Permission::whereIn('name', $permNames)->pluck('name')->toArray();
            $role->syncPermissions($permissions);
        }

        return $role;
    }
}
