<?php

namespace App\Exports;

use Spatie\Permission\Models\Permission;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PermissionExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Permission::all();
    }

    public function headings(): array
    {
        return ['Name', 'Guard', 'Group', 'Label'];
    }

    public function map($permission): array
    {
        return [
            $permission->name,
            $permission->guard_name,
            $permission->group ?? '',
            $permission->label ?? '',
        ];
    }
}
