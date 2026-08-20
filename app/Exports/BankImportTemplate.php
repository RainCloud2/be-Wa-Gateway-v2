<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BankImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['Bank Central Asia', 'BCA', 'Bank swasta terbesar di Indonesia'],
            ['Bank Rakyat Indonesia', 'BRI', 'Bank milik pemerintah'],
        ];
    }

    public function headings(): array
    {
        return ['name', 'initial', 'description'];
    }
}
