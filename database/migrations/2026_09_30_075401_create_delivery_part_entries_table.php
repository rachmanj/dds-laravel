<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_part_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('ito_no')->nullable();
            $table->string('ito_no_override')->nullable();
            $table->string('item_code')->nullable();
            $table->string('unit_no')->nullable();
            $table->enum('source', ['sap', 'manual'])->default('sap');
            $table->string('no_spb')->nullable();
            $table->text('remarks_barang')->nullable();
            $table->date('tgl_delivery')->nullable();
            $table->string('transporter')->nullable();
            $table->string('unit_kendaraan')->nullable();
            $table->string('ekspedisi')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'ito_no', 'item_code', 'unit_no'], 'delivery_part_entries_project_line_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_part_entries');
    }
};
