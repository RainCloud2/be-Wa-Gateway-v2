<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('mode', 10)->default('ai');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->boolean('awaiting_ticket_confirmation')->default(false);
            // Nullable link to the ticket. The FK is added by the tickets migration
            // to avoid a circular dependency between the two tables.
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
