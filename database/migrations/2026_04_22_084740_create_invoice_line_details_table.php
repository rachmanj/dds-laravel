<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_line_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('line_no');
            $table->text('description');
            $table->decimal('quantity', 20, 4)->nullable();
            $table->decimal('unit_price', 20, 4)->nullable();
            $table->decimal('amount', 20, 2)->nullable();
            $table->string('source', 20)->default('import');
            $table->timestamps();

            $table->unique(['invoice_id', 'line_no']);
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_details');
    }
};
