<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_warehouse_projects', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('logistics_warehouse_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
