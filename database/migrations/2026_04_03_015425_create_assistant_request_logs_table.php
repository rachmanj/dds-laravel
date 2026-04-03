<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistant_conversation_id')->nullable()->constrained('assistant_conversations')->nullOnDelete();
            $table->string('status', 32);
            $table->json('tools_invoked')->nullable();
            $table->boolean('show_all_records')->default(false);
            $table->unsignedInteger('user_message_length')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error_summary', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_request_logs');
    }
};
