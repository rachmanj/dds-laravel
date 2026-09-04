<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->index('grpo_no', 'additional_documents_grpo_no_index');
        });
    }

    public function down(): void
    {
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->dropIndex('additional_documents_grpo_no_index');
        });
    }
};
