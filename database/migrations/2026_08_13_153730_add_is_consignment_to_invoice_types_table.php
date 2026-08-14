<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_types', function (Blueprint $table) {
            $table->boolean('is_consignment')->default(false)->after('type_name');
        });

        if (DB::table('invoice_types')->exists()) {
            if (DB::table('invoice_types')->where('type_name', 'Consignment')->exists()) {
                DB::table('invoice_types')
                    ->where('type_name', 'Consignment')
                    ->update(['is_consignment' => true]);
            } else {
                DB::table('invoice_types')->insert([
                    'type_name' => 'Consignment',
                    'is_consignment' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('invoice_types', function (Blueprint $table) {
            $table->dropColumn('is_consignment');
        });
    }
};
