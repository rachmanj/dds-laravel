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
        Schema::create('sap_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('action');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->integer('attempt_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sap_logs');
    }
};
