<?php

namespace App\Exports;

use App\Models\Worker;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WorkerExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Worker::with('bank')->get();
    }

    public function headings(): array
    {
        return [
            'Ops ID', 'Full Name', 'NIK', 'Phone Number', 'Bank', 'Account Number',
            'Domicile Address', 'Emergency Contact Number',
            'Emergency Contact Name', 'Emergency Contact Relation',
        ];
    }

    public function map($worker): array
    {
        return [
            $worker->ops_id,
            $worker->full_name,
            $worker->nik,
            $worker->phone_number ?? '',
            $worker->bank?->name ?? '',
            $worker->account_number ?? '',
            $worker->domicile_address ?? '',
            $worker->emergency_contact_number ?? '',
            $worker->emergency_contact_name ?? '',
            $worker->emergency_contact_relation ?? '',
        ];
    }
}
