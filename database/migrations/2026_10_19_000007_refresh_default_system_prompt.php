<?php

use App\Services\Wa\SystemPrompt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Refresh the stored system prompt for installs that still hold the
     * original default, so the hardened prompt (which forbids the model from
     * claiming a ticket was created) takes effect. A customization made by an
     * admin is left untouched.
     */
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'system_prompt')->first();

        if (! $row || ! is_string($row->value)) {
            return;
        }

        $isOriginalDefault = str_contains($row->value, 'Anda adalah asisten IT support')
            && ! str_contains($row->value, 'JANGAN pernah menulis bahwa tiket');

        if ($isOriginalDefault) {
            DB::table('settings')->where('key', 'system_prompt')->update([
                'value' => SystemPrompt::default(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
