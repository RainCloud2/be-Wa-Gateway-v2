<?php

namespace App\Exports;

use App\Models\Bank;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class WorkerBankTemplateSheet implements FromCollection, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Daftar Bank';
    }

    public function collection()
    {
        return Bank::orderBy('name')->get(['name']);
    }

    public function headings(): array
    {
        return ['Nama Bank'];
    }
}
