<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('sap_submitted_by_user_id')
                ->nullable()
                ->after('sap_last_attempted_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('sap_submitted_at')->nullable()->after('sap_submitted_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['sap_submitted_by_user_id']);
            $table->dropColumn(['sap_submitted_by_user_id', 'sap_submitted_at']);
        });
    }
};
