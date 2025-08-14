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
        Schema::create('distribution_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained('distributions')->onDelete('cascade');

            // Polymorphic document association
            $table->string('document_type'); // App\Models\Invoice or App\Models\AdditionalDocument
            $table->unsignedBigInteger('document_id'); // ID of the actual document

            // Document-level verification
            $table->boolean('sender_verified')->default(false);
            $table->enum('sender_verification_status', ['verified', 'missing', 'damaged'])->nullable();
            $table->text('sender_verification_notes')->nullable();

            $table->boolean('receiver_verified')->default(false);
            $table->enum('receiver_verification_status', ['verified', 'missing', 'damaged'])->nullable();
            $table->text('receiver_verification_notes')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['document_type', 'document_id']);
            $table->index('distribution_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_documents');
    }
};
