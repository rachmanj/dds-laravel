<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_part_entries', function (Blueprint $table) {
            $table->date('tanggal_received')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_part_entries', function (Blueprint $table) {
            $table->dropColumn('tanggal_received');
        });
    }
};
