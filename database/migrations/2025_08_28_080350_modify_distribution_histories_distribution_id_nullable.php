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
        Schema::table('distribution_histories', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['distribution_id']);

            // Make distribution_id nullable
            $table->foreignId('distribution_id')->nullable()->change();

            // Re-add the foreign key constraint with nullable support
            $table->foreign('distribution_id')->references('id')->on('distributions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_histories', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['distribution_id']);

            // Make distribution_id required again
            $table->foreignId('distribution_id')->nullable(false)->change();

            // Re-add the foreign key constraint
            $table->foreign('distribution_id')->references('id')->on('distributions')->onDelete('cascade');
        });
    }
};
