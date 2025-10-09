<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration addresses the issue where soft-deleted distributions
     * block new distributions from using the same sequence number.
     * 
     * The solution is implemented at the application level in the
     * Distribution model's getNextSequence() method, which now
     * excludes soft-deleted records when calculating the next sequence.
     */
    public function up(): void
    {
        // No database changes needed - handled at application level
        // The getNextSequence() method in Distribution model now uses:
        // ->whereNull('deleted_at') to exclude soft-deleted records
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes to reverse
    }
};
