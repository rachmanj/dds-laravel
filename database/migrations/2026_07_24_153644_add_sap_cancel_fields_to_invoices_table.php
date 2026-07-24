<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('sap_cancelled_at')->nullable()->after('sap_last_attempted_at');
            $table->text('sap_cancel_error_message')->nullable()->after('sap_cancelled_at');
            $table->string('sap_cancellation_doc_num')->nullable()->after('sap_cancel_error_message');
            $table->string('sap_cancellation_doc_entry')->nullable()->after('sap_cancellation_doc_num');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'sap_cancelled_at',
                'sap_cancel_error_message',
                'sap_cancellation_doc_num',
                'sap_cancellation_doc_entry',
            ]);
        });
    }
};
