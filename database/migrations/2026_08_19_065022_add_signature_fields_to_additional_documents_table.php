<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->string('signature_status')->nullable()->after('batch_no');
            $table->foreignId('signature_project_id')->nullable()->after('signature_status')->constrained('projects')->nullOnDelete();
            $table->foreignId('signature_checked_by')->nullable()->after('signature_project_id')->constrained('users')->nullOnDelete();
            $table->timestamp('signature_checked_at')->nullable()->after('signature_checked_by');
            $table->text('signature_override_reason')->nullable()->after('signature_checked_at');
            $table->foreignId('signature_override_by')->nullable()->after('signature_override_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('signature_override_at')->nullable()->after('signature_override_by');
        });
    }

    public function down(): void
    {
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->dropForeign(['signature_project_id']);
            $table->dropForeign(['signature_checked_by']);
            $table->dropForeign(['signature_override_by']);
            $table->dropColumn([
                'signature_status',
                'signature_project_id',
                'signature_checked_by',
                'signature_checked_at',
                'signature_override_reason',
                'signature_override_by',
                'signature_override_at',
            ]);
        });
    }
};
