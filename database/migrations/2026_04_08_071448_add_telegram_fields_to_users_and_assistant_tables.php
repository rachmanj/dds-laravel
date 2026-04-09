<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_user_id')->nullable()->unique()->after('is_active');
            $table->string('telegram_username', 255)->nullable()->after('telegram_user_id');
        });

        Schema::table('assistant_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_chat_id')->nullable()->after('user_id');
            $table->index(['user_id', 'telegram_chat_id']);
        });

        Schema::table('assistant_request_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_chat_id')->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_request_logs', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });

        Schema::table('assistant_conversations', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'telegram_chat_id']);
            $table->dropColumn('telegram_chat_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_user_id', 'telegram_username']);
        });
    }
};
