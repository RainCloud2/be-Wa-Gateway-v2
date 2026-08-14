<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['John Doe', 'john@example.com', 'admin'],
            ['Jane Doe', 'jane@example.com', 'editor'],
        ];
    }

    public function headings(): array
    {
        return ['name', 'email', 'roles'];
    }
}
