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
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->string('distribution_number')->unique(); // Auto-generated: YY/LOCATION/DDS/0001
            $table->foreignId('type_id')->constrained('distribution_types');
            $table->foreignId('origin_department_id')->constrained('departments');
            $table->foreignId('destination_department_id')->constrained('departments');
            $table->enum('document_type', ['invoice', 'additional_document']);
            $table->foreignId('created_by')->constrained('users');

            // Workflow status
            $table->enum('status', [
                'draft',
                'verified_by_sender',
                'sent',
                'received',
                'verified_by_receiver',
                'completed'
            ])->default('draft');

            // Workflow timestamps
            $table->timestamp('sender_verified_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('receiver_verified_at')->nullable();

            // Verification tracking
            $table->foreignId('sender_verified_by')->nullable()->constrained('users');
            $table->text('sender_verification_notes')->nullable();
            $table->foreignId('receiver_verified_by')->nullable()->constrained('users');
            $table->text('receiver_verification_notes')->nullable();
            $table->boolean('has_discrepancies')->default(false);

            // General notes
            $table->text('notes')->nullable();

            // Automatic numbering system fields
            $table->year('year'); // For sequence tracking
            $table->unsignedInteger('sequence'); // Auto-incremented per department/year

            // Soft delete
            $table->softDeletes();
            $table->timestamps();

            // Unique constraint for automatic numbering
            $table->unique(['year', 'origin_department_id', 'sequence'], 'distributions_year_dept_seq_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};
