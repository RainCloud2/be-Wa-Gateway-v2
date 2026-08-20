<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            ['Bank Rakyat Indonesia', 'BRI', 'Bank milik pemerintah, terbesar di Indonesia'],
            ['Bank Central Asia', 'BCA', 'Bank swasta terbesar di Indonesia'],
            ['Bank Mandiri', 'Mandiri', 'Bank milik pemerintah (BUMN)'],
            ['Bank Negara Indonesia', 'BNI', 'Bank milik pemerintah (BUMN)'],
            ['Bank Tabungan Negara', 'BTN', 'Bank milik pemerintah, fokus KPR'],
            ['Bank Syariah Indonesia', 'BSI', 'Bank syariah hasil merger'],
            ['Bank Danamon', 'Danamon', 'Bank swasta (MUFG Group)'],
            ['CIMB Niaga', 'CIMB Niaga', 'Bank swasta (CIMB Group)'],
            ['Bank Permata', 'Permata', 'Bank swasta hasil merger'],
            ['Maybank Indonesia', 'Maybank', 'Bank swasta (Maybank Group)'],
            ['Pan Indonesia Bank', 'Panin', 'Bank swasta nasional'],
            ['Bank OCBC NISP', 'OCBC', 'Bank swasta (OCBC Group)'],
            ['Bank Mega', 'Mega', 'Bank swasta (CT Corp)'],
            ['Bank Sinarmas', 'Sinarmas', 'Bank swasta (Sinar Mas Group)'],
            ['Bank BTPN', 'BTPN', 'Bank swasta (SME focus)'],
            ['Bank BTPN Syariah', 'BTPN Syariah', 'Bank syariah'],
            ['Bank Muamalat Indonesia', 'Muamalat', 'Bank syariah pertama di Indonesia'],
            ['Bank KB Bukopin', 'KB Bukopin', 'Bank swasta'],
            ['Bank Mayapada', 'Mayapada', 'Bank swasta'],
            ['Bank Artha Graha Internasional', 'Artha Graha', 'Bank swasta'],
            ['Bank Victoria International', 'Victoria', 'Bank swasta'],
            ['Bank Ganesha', 'Ganesha', 'Bank swasta'],
            ['Bank Maspion', 'Maspion', 'Bank swasta'],
            ['Bank Woori Saudara', 'Woori Saudara', 'Bank swasta (Woori Group)'],
            ['Bank JTrust Indonesia', 'JTrust', 'Bank swasta'],
            ['Bank QNB Indonesia', 'QNB', 'Bank swasta (QNB Group)'],
            ['Bank Ina Perdana', 'Ina Perdana', 'Bank swasta'],
            ['Bank Jago', 'Jago', 'Bank digital'],
            ['Bank Neo Commerce', 'Neo', 'Bank digital'],
            ['Bank Digital BCA', 'Blu', 'Bank digital (BCA Group)'],
            ['Bank HSBC Indonesia', 'HSBC', 'Bank asing (HSBC Group)'],
            ['Bank DBS Indonesia', 'DBS', 'Bank asing (DBS Group)'],
            ['Bank UOB Indonesia', 'UOB', 'Bank asing (UOB Group)'],
            ['Standard Chartered Bank', 'Stanchart', 'Bank asing'],
            ['BPD DKI Jakarta', 'BPD DKI', 'Bank Pembangunan Daerah'],
            ['Bank Jatim', 'BPD Jatim', 'Bank Pembangunan Daerah'],
            ['Bank Jateng', 'BPD Jateng', 'Bank Pembangunan Daerah'],
            ['Bank Sumsel Babel', 'BPD Sumsel Babel', 'Bank Pembangunan Daerah'],
        ];

        foreach ($banks as [$name, $initial, $description]) {
            Bank::updateOrCreate(
                ['name' => $name],
                ['initial' => $initial, 'description' => $description]
            );
        }
    }
}
