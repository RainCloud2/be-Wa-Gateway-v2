<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class WorkerInstructionTemplateSheet implements FromArray, WithTitle
{
    public function title(): string
    {
        return 'Cara Pengisian';
    }

    public function array(): array
    {
        return [
            ['CARA PENGISIAN TEMPLATE IMPORT WORKER'],
            [''],
            ['Panduan umum:'],
            ['- Isi data pada sheet "Data Worker". Jangan mengubah/menghapus baris judul (header).'],
            ['- Sheet "Daftar Bank" berisi daftar nama bank. Gunakan nama bank yang sama persis dengan yang ada di sheet tersebut.'],
            ['- Baris contoh (contoh: OPS-001) hanya sebagai panduan, bisa dihapus sebelum di-import.'],
            [''],
            ['KETERANGAN KOLOM:'],
            ['ops_id', 'Ops ID (identitas unik pekerja)', 'WAJIB DIISI'],
            ['full_name', 'Nama lengkap pekerja', 'WAJIB DIISI'],
            ['nik', 'Nomor Induk Kependudukan (NIK)', 'WAJIB DIISI'],
            ['phone_number', 'Nomor HP pekerja', 'OPSIONAL'],
            ['bank', 'Nama bank (pilih dari sheet "Daftar Bank")', 'OPSIONAL'],
            ['account_number', 'Nomor rekening bank', 'OPSIONAL'],
            ['domicile_address', 'Alamat domisili pekerja', 'OPSIONAL'],
            ['emergency_contact_number', 'Nomor kontak darurat', 'OPSIONAL'],
            ['emergency_contact_name', 'Nama kontak darurat', 'OPSIONAL'],
            ['emergency_contact_relation', 'Hubungan dengan kontak darurat (cth: Suami/Istri, Orang Tua)', 'OPSIONAL'],
            [''],
            ['CATATAN UNTUK KOLOM ANGKA:'],
            ["Untuk kolom berisi angka (NIK, nomor rekening, nomor kontak), ketik tanda kutip (') di depan angkanya agar Excel memperlakukan data sebagai teks dan tidak mengubah formatnya."],
            ['Contoh: ketik \'081234567890 atau \'3171234567890001 (bukan tanpa tanda kutip).'],
            ['Tanpa tanda kutip, Excel bisa mengubah angka menjadi notasi ilmiah (misal 3.2E+15) sehingga data NIK/rekening menjadi salah.'],
        ];
    }
}
