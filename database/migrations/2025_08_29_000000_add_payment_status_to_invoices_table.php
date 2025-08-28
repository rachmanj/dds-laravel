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
            $table->enum('payment_status', ['pending', 'paid'])->default('pending')->after('payment_date');
            $table->foreignId('paid_by')->nullable()->constrained('users')->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('paid_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['payment_status', 'paid_by', 'paid_at']);
        });
    }
};
