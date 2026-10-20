<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\Wa\SystemPrompt;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'max_attempts' => 3,
            'system_prompt' => SystemPrompt::default(),
            'welcome_message' => 'Halo! Saya asisten IT support kantor. Silakan ceritakan kendala perangkat, software, atau jaringan yang Anda alami.',
            'business_hours' => [
                'days' => [1, 2, 3, 4, 5],
                'start' => '08:00',
                'end' => '17:00',
                'timezone' => 'Asia/Jakarta',
            ],
            'it_notify_numbers' => [],
            'auto_close_resolved_hours' => 24,
            'session_timeout_hours' => 6,
        ];

        foreach ($defaults as $key => $value) {
            if (! Setting::query()->where('key', $key)->exists()) {
                Setting::query()->create([
                    'key' => $key,
                    'value' => is_string($value) ? $value : json_encode($value),
                ]);
            }
        }

        Setting::flushCache();
    }
}
