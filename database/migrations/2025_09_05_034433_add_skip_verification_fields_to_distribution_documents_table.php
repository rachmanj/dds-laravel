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
        Schema::table('distribution_documents', function (Blueprint $table) {
            $table->string('origin_cur_loc')->nullable()->after('document_id');
            $table->boolean('skip_verification')->default(false)->after('origin_cur_loc');

            $table->index('skip_verification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_documents', function (Blueprint $table) {
            $table->dropIndex(['skip_verification']);
            $table->dropColumn(['origin_cur_loc', 'skip_verification']);
        });
    }
};
