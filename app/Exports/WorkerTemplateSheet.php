<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class WorkerTemplateSheet implements FromArray, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Data Worker';
    }

    public function array(): array
    {
        return [
            ['OPS-001', 'John Doe', "'3171234567890001", "'081234567890", 'Bank Central Asia', "'1234567890", 'Jl. Merdeka No. 1', "'081987654321", 'Jane Doe', 'Wife'],
        ];
    }

    public function headings(): array
    {
        return [
            'ops_id', 'full_name', 'nik', 'phone_number', 'bank', 'account_number',
            'domicile_address', 'emergency_contact_number',
            'emergency_contact_name', 'emergency_contact_relation',
        ];
    }
}
