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
        // Add indexes for additional_documents table
        Schema::table('additional_documents', function (Blueprint $table) {
            // Add index for current location queries
            $table->index(['cur_loc', 'distribution_status'], 'idx_ad_cur_loc_status');
            $table->index(['cur_loc', 'created_at'], 'idx_ad_cur_loc_created');
            $table->index(['cur_loc', 'receive_date'], 'idx_ad_cur_loc_receive');
        });

        // Add indexes for invoices table
        Schema::table('invoices', function (Blueprint $table) {
            // Add index for current location queries
            $table->index(['cur_loc', 'distribution_status'], 'idx_inv_cur_loc_status');
            $table->index(['cur_loc', 'created_at'], 'idx_inv_cur_loc_created');
            $table->index(['cur_loc', 'receive_date'], 'idx_inv_cur_loc_receive');
        });

        // Add indexes for distributions table
        Schema::table('distributions', function (Blueprint $table) {
            // Add index for distribution queries
            $table->index(['destination_department_id', 'received_at'], 'idx_dist_dest_received');
            $table->index(['status', 'received_at'], 'idx_dist_status_received');
            $table->index(['origin_department_id', 'sent_at'], 'idx_dist_origin_sent');
        });

        // Add indexes for distribution_documents table
        Schema::table('distribution_documents', function (Blueprint $table) {
            // Add index for document-distribution relationships
            $table->index(['document_id', 'document_type', 'receiver_verification_status'], 'idx_dist_doc_verification');
            $table->index(['distribution_id', 'document_type'], 'idx_dist_doc_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for additional_documents table
        Schema::table('additional_documents', function (Blueprint $table) {
            $table->dropIndex('idx_ad_cur_loc_status');
            $table->dropIndex('idx_ad_cur_loc_created');
            $table->dropIndex('idx_ad_cur_loc_receive');
        });

        // Drop indexes for invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_inv_cur_loc_status');
            $table->dropIndex('idx_inv_cur_loc_created');
            $table->dropIndex('idx_inv_cur_loc_receive');
        });

        // Drop indexes for distributions table
        Schema::table('distributions', function (Blueprint $table) {
            $table->dropIndex('idx_dist_dest_received');
            $table->dropIndex('idx_dist_status_received');
            $table->dropIndex('idx_dist_origin_sent');
        });

        // Drop indexes for distribution_documents table
        Schema::table('distribution_documents', function (Blueprint $table) {
            $table->dropIndex('idx_dist_doc_verification');
            $table->dropIndex('idx_dist_doc_type');
        });
    }
};
