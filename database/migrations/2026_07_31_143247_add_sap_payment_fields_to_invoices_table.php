<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('sap_payment_status')->nullable()->after('sap_cancellation_doc_entry');
            $table->string('sap_payment_doc_num')->nullable()->after('sap_payment_status');
            $table->string('sap_payment_doc_entry')->nullable()->after('sap_payment_doc_num');
            $table->string('sap_payment_means')->nullable()->after('sap_payment_doc_entry');
            $table->string('sap_payment_account_code')->nullable()->after('sap_payment_means');
            $table->text('sap_payment_error_message')->nullable()->after('sap_payment_account_code');
            $table->timestamp('sap_payment_last_attempted_at')->nullable()->after('sap_payment_error_message');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'sap_payment_status',
                'sap_payment_doc_num',
                'sap_payment_doc_entry',
                'sap_payment_means',
                'sap_payment_account_code',
                'sap_payment_error_message',
                'sap_payment_last_attempted_at',
            ]);
        });
    }
};
