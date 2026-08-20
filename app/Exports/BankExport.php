<?php

namespace App\Exports;

use App\Models\Bank;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BankExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Bank::all();
    }

    public function headings(): array
    {
        return ['Name', 'Initial', 'Description'];
    }

    public function map($bank): array
    {
        return [
            $bank->name,
            $bank->initial,
            $bank->description ?? '',
        ];
    }
}
