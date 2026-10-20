<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('status', 20)->default('OPEN');
            $table->string('priority', 10)->default('normal');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('assignee_id');
            $table->index('category');
        });

        // Now that tickets exists, wire the session -> ticket relation.
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->foreign('ticket_id')->references('id')->on('tickets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
        });

        Schema::dropIfExists('tickets');
    }
};
