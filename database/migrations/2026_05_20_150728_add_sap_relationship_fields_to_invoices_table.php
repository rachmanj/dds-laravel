<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('sap_doc_entry')->nullable()->after('sap_doc_num');
            $table->json('sap_grpo_references')->nullable()->after('sap_doc_entry');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['sap_doc_entry', 'sap_grpo_references']);
        });
    }
};
