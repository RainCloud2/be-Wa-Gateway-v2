<?php

namespace App\Imports;

use App\Models\Bank;
use App\Models\Worker;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkerImport implements ToModel, WithHeadingRow, WithMultipleSheets
{
    public function sheets(): array
    {
        return [0 => $this];
    }

    public function model(array $row)
    {
        $bank = null;
        if (! empty($row['bank'])) {
            $bank = Bank::where('name', $row['bank'])->first();
        }

        return Worker::updateOrCreate(
            ['ops_id' => $this->cleanNumber($row['ops_id'] ?? '')],
            [
                'full_name' => trim((string) ($row['full_name'] ?? '')),
                'nik' => $this->cleanNumber($row['nik'] ?? ''),
                'phone_number' => $this->cleanNumber($row['phone_number'] ?? ''),
                'bank_id' => $bank?->id,
                'account_number' => $this->cleanNumber($row['account_number'] ?? ''),
                'domicile_address' => trim((string) ($row['domicile_address'] ?? '')),
                'emergency_contact_number' => $this->cleanNumber($row['emergency_contact_number'] ?? ''),
                'emergency_contact_name' => trim((string) ($row['emergency_contact_name'] ?? '')),
                'emergency_contact_relation' => trim((string) ($row['emergency_contact_relation'] ?? '')),
            ]
        );
    }

    private function cleanNumber($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return rtrim(rtrim(sprintf('%.0f', (float) $value), '0'), '.');
        }

        $value = ltrim(trim((string) $value), "'`");

        return $value === '' ? null : $value;
    }
}
