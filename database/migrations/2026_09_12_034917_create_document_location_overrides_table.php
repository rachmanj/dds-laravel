<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_location_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->unsignedBigInteger('document_id')->index();
            $table->string('from_loc', 50);
            $table->string('to_loc', 50);
            $table->text('reason');
            $table->unsignedBigInteger('overridden_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_location_overrides');
    }
};
