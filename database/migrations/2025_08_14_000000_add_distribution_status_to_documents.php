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
        // Add distribution_status to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('distribution_status', ['available', 'in_transit', 'distributed'])->default('available')->after('status');
            $table->index('distribution_status');
        });

        // Add distribution_status to additional_documents table
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->enum('distribution_status', ['available', 'in_transit', 'distributed'])->default('available')->after('status');
            $table->index('distribution_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove distribution_status from invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['distribution_status']);
            $table->dropColumn('distribution_status');
        });

        // Remove distribution_status from additional_documents table
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->dropIndex(['distribution_status']);
            $table->dropColumn('distribution_status');
        });
    }
};
