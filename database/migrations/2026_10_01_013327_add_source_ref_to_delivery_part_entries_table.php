<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_part_entries', function (Blueprint $table) {
            $table->string('source_ref', 64)->nullable()->after('source');
            $table->unique(['project_id', 'source_ref'], 'delivery_part_entries_project_source_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_part_entries', function (Blueprint $table) {
            $table->dropUnique('delivery_part_entries_project_source_ref_unique');
            $table->dropColumn('source_ref');
        });
    }
};
