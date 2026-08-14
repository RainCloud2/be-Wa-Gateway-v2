<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PermissionImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['blog.create', 'web', 'blog', 'Create Blog'],
            ['blog.read', 'web', 'blog', 'Read Blog'],
        ];
    }

    public function headings(): array
    {
        return ['name', 'guard', 'group', 'label'];
    }
}
