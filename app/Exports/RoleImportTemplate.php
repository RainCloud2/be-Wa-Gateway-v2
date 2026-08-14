<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RoleImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['editor', 'blog.create, blog.read'],
            ['moderator', 'blog.create, blog.read, blog.update'],
        ];
    }

    public function headings(): array
    {
        return ['name', 'permissions'];
    }
}
