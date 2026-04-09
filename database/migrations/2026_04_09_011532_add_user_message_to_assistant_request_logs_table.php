<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assistant_request_logs', function (Blueprint $table) {
            $table->text('user_message')->nullable()->after('user_message_length');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistant_request_logs', function (Blueprint $table) {
            $table->dropColumn('user_message');
        });
    }
};
