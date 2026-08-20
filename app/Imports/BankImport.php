<?php

namespace App\Imports;

use App\Models\Bank;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BankImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return Bank::firstOrCreate(
            ['name' => $row['name'] ?? ''],
            [
                'initial' => $row['initial'] ?? '',
                'description' => $row['description'] ?? null,
            ]
        );
    }
}
