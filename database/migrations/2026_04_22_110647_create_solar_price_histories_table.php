<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solar_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_line_detail_id')->nullable()->constrained('invoice_line_details')->nullOnDelete();
            $table->decimal('unit_price', 20, 4);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('quantity', 20, 4)->nullable();
            $table->decimal('amount', 20, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solar_price_histories');
    }
};
