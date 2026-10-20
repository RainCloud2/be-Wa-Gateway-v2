<?php

use App\Services\Wa\SystemPrompt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Refresh the stored system prompt for installs that still hold the
     * previous default, so the model stops offering tickets on its own (the
     * application controls the offer). Admin customisations are left alone.
     */
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'system_prompt')->first();

        if (! $row || ! is_string($row->value)) {
            return;
        }

        $isPreviousDefault = str_contains($row->value, 'JANGAN pernah menulis bahwa tiket')
            && ! str_contains($row->value, 'JANGAN menawarkan pembuatan tiket atas inisiatif sendiri');

        if ($isPreviousDefault) {
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
