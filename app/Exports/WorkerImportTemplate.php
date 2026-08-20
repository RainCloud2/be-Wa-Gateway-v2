<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkerImportTemplate implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Data Worker' => new WorkerTemplateSheet,
            'Daftar Bank' => new WorkerBankTemplateSheet,
            'Cara Pengisian' => new WorkerInstructionTemplateSheet,
        ];
    }
}
