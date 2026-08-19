<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('additional_document_id')->constrained('additional_documents')->cascadeOnDelete();
            $table->foreignId('specimen_id')->nullable()->constrained('signature_specimens')->nullOnDelete();
            $table->decimal('score', 4, 3)->nullable();
            $table->string('verdict');
            $table->string('model');
            $table->text('raw_response')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_match_results');
    }
};
