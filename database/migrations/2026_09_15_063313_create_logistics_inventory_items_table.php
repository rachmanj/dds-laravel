<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('logistics_inventory_snapshots')->cascadeOnDelete();
            $table->string('model_no')->nullable();
            $table->string('unit_no')->nullable();
            $table->string('item_code');
            $table->string('item_name')->nullable();
            $table->string('category');
            $table->string('uom')->nullable();
            $table->decimal('instock', 20, 4)->default(0);
            $table->decimal('committed', 20, 4)->default(0);
            $table->decimal('ordered', 20, 4)->default(0);
            $table->string('currency', 10)->nullable();
            $table->decimal('last_price', 20, 4)->nullable();
            $table->decimal('total_value', 20, 2)->default(0);
            $table->string('whs_code')->nullable();
            $table->string('whs_name')->nullable();
            $table->string('project')->nullable();
            $table->string('status')->nullable();
            $table->string('last_mr_no')->nullable();
            $table->string('last_mi_no')->nullable();

            $table->index(['snapshot_id', 'project']);
            $table->index(['snapshot_id', 'category']);
            $table->index(['snapshot_id', 'whs_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_inventory_items');
    }
};
