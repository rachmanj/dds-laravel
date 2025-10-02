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
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->string('vendor_code', 50)->nullable()->after('po_no');
            $table->index('vendor_code'); // For performance on PO suggestions
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->dropIndex(['vendor_code']);
            $table->dropColumn('vendor_code');
        });
    }
};
