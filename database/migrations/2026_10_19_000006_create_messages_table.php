<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('sender', 10);
            $table->text('body')->nullable();
            $table->string('media_type', 20)->nullable();
            $table->string('wa_message_id')->nullable()->unique();
            $table->boolean('is_internal')->default(false);
            $table->string('delivery_status', 10)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('ticket_id');
            $table->index('sender');
            $table->index('delivery_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
