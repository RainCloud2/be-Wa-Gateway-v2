<?php

namespace App\Exports;

use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RoleExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Role::with('permissions')->get();
    }

    public function headings(): array
    {
        return ['Name', 'Permissions'];
    }

    public function map($role): array
    {
        return [
            $role->name,
            $role->permissions->pluck('name')->implode(', '),
        ];
    }
}
