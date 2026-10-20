<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('workers');
        Schema::dropIfExists('banks');
    }

    public function down(): void
    {
        //
    }
};
