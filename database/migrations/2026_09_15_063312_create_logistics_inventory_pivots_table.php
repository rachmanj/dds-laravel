<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_inventory_pivots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('logistics_inventory_snapshots')->cascadeOnDelete();
            $table->string('project')->nullable();
            $table->string('category');
            $table->decimal('sum_instock', 20, 4)->default(0);
            $table->decimal('sum_value', 20, 2)->default(0);

            $table->index(['snapshot_id', 'project', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_inventory_pivots');
    }
};
