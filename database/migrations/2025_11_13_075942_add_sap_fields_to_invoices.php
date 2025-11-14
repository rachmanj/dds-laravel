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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'sap_status')) {
                $table->string('sap_status')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'sap_doc_num')) {
                $table->string('sap_doc_num')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'sap_error_message')) {
                $table->text('sap_error_message')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'sap_last_attempted_at')) {
                $table->timestamp('sap_last_attempted_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['sap_status', 'sap_doc_num', 'sap_error_message', 'sap_last_attempted_at']);
        });
    }
};
