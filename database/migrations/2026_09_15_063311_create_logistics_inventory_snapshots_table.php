<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->string('status');
            $table->unsignedInteger('row_count')->default(0);
            $table->decimal('total_value', 20, 2)->default(0);
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index('snapshot_date');
            $table->index(['status', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_inventory_snapshots');
    }
};
