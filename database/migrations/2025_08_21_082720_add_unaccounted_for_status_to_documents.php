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
        // Update invoices table
        Schema::table('invoices', function (Blueprint $table) {
            // Modify the existing enum to include 'unaccounted_for'
            $table->enum('distribution_status', ['available', 'in_transit', 'distributed', 'unaccounted_for'])
                ->default('available')
                ->change();
        });

        // Update additional_documents table
        Schema::table('additional_documents', function (Blueprint $table) {
            // Modify the existing enum to include 'unaccounted_for'
            $table->enum('distribution_status', ['available', 'in_transit', 'distributed', 'unaccounted_for'])
                ->default('available')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('distribution_status', ['available', 'in_transit', 'distributed'])
                ->default('available')
                ->change();
        });

        // Revert additional_documents table
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->enum('distribution_status', ['available', 'in_transit', 'distributed'])
                ->default('available')
                ->change();
        });
    }
};
