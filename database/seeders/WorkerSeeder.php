<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Worker;
use Faker\Factory;
use Illuminate\Database\Seeder;

class WorkerSeeder extends Seeder
{
    public function run(): void
    {
        $bankIds = Bank::orderBy('name')->pluck('id')->toArray();
        $relations = ['Suami', 'Istri', 'Ayah', 'Ibu', 'Kakak', 'Adik', 'Saudara', 'Teman'];
        $faker = Factory::create('id_ID');

        for ($i = 1; $i <= 100; $i++) {
            $opsId = 'OPS-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $useBank = $faker->boolean(85);
            $bankId = $useBank ? $faker->randomElement($bankIds) : null;

            Worker::updateOrCreate(
                ['ops_id' => $opsId],
                [
                    'full_name' => $faker->name(),
                    'nik' => $faker->unique()->nik(),
                    'phone_number' => str_replace(' ', '', $faker->phoneNumber()),
                    'bank_id' => $bankId,
                    'account_number' => $bankId ? $faker->numerify('##########') : null,
                    'domicile_address' => $faker->address(),
                    'emergency_contact_number' => str_replace(' ', '', $faker->phoneNumber()),
                    'emergency_contact_name' => $faker->name(),
                    'emergency_contact_relation' => $faker->randomElement($relations),
                    'ktp_photo' => null,
                    'self_photo' => null,
                ]
            );
        }
    }
}
