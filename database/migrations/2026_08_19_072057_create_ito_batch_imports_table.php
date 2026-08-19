<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ito_batch_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('stored_path');
            $table->unsignedInteger('total_pages')->default(0);
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ito_batch_imports');
    }
};
