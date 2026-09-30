<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_part_ito_cancels', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('doc_entry');
            $table->string('ito_no');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('item_code')->nullable();
            $table->string('unit_no')->nullable();
            $table->text('reason');
            $table->enum('status', ['requested', 'cancelled', 'failed'])->default('requested');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('sap_message')->nullable();
            $table->timestamps();

            $table->index('doc_entry');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_part_ito_cancels');
    }
};
