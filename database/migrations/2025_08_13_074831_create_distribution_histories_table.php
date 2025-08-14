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
        Schema::create('distribution_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained('distributions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Who performed the action

            // Action details
            $table->string('action'); // created, verified_by_sender, sent, received, verified_by_receiver, completed, updated, etc.
            $table->string('action_type'); // workflow_transition, document_verification, discrepancy_reported, etc.

            // Status changes
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();

            // Document-specific actions
            $table->unsignedBigInteger('document_id')->nullable(); // If action is document-specific
            $table->string('document_type')->nullable(); // Document model class

            // Action metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional data like verification details, discrepancy info

            // Timestamps
            $table->timestamp('action_performed_at');
            $table->timestamps();

            // Indexes for performance
            $table->index('distribution_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('action_performed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_histories');
    }
};
