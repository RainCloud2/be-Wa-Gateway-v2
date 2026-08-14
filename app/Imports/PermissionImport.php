<?php

namespace App\Imports;

use Spatie\Permission\Models\Permission;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PermissionImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return Permission::firstOrCreate(
            ['name' => $row['name'] ?? ''],
            [
                'guard_name' => $row['guard'] ?? 'web',
                'group' => $row['group'] ?? null,
                'label' => $row['label'] ?? null,
            ]
        );
    }
}
