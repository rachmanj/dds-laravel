<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ito_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('ito_batch_imports')->cascadeOnDelete();
            $table->unsignedInteger('page_number');
            $table->string('extracted_ito_no')->nullable();
            $table->foreignId('matched_document_id')->nullable()->constrained('additional_documents')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->decimal('confidence', 4, 3)->nullable();
            $table->string('attached_path')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ito_batch_items');
    }
};
