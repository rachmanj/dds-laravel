<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_specimen_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')->constrained('signature_specimens')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unique(['specimen_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_specimen_project');
    }
};
