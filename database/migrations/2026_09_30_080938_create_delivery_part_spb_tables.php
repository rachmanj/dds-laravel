<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_part_spb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('no_spb');
            $table->date('tanggal')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'no_spb'], 'delivery_part_spb_project_no_unique');
        });

        Schema::create('delivery_part_spb_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_part_spb_id')->constrained('delivery_part_spb')->cascadeOnDelete();
            $table->string('part_number')->nullable();
            $table->string('description')->nullable();
            $table->decimal('qty', 18, 4)->nullable();
            $table->string('uom')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_part_spb_items');
        Schema::dropIfExists('delivery_part_spb');
    }
};
