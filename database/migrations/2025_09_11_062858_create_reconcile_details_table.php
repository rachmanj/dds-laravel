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
        Schema::create('reconcile_details', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->nullable()->comment('External invoice number from uploaded file');
            $table->foreignId('vendor_id')->nullable()->constrained('suppliers')->onDelete('set null')->comment('Supplier ID for the invoice');
            $table->date('invoice_date')->nullable()->comment('Invoice date from uploaded file');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('User who uploaded the data');
            $table->string('flag', 20)->nullable()->comment('Temporary flag for upload process isolation');
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('invoice_no');
            $table->index('vendor_id');
            $table->index('flag');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconcile_details');
    }
};
