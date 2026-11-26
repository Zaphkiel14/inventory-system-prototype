<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the hot read paths: batch allocation (FEFO/FIFO per item over
 * usable stock), the requisition queues by status, issuance/approval lookups
 * by parent, ledger lookups by batch and user, and item lists by status.
 * SQLite does not index foreign keys on its own, so the FK columns here
 * were unindexed before.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->index(
                ['inventory_item_id', 'remaining_qty', 'expiration_date', 'received_at'],
                'inventory_batches_item_remaining_expiry_received_index',
            );
            $table->index('stock_receipt_id', 'inventory_batches_stock_receipt_id_index');
            $table->index('storage_location_id', 'inventory_batches_storage_location_id_index');
        });

        Schema::table('requisitions', function (Blueprint $table) {
            $table->index(['status', 'submitted_at'], 'requisitions_status_submitted_at_index');
        });

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->index('requisition_id', 'requisition_items_requisition_id_index');
            $table->index(['inventory_item_id', 'outstanding_qty'], 'requisition_items_item_outstanding_index');
        });

        Schema::table('stock_issuance_batches', function (Blueprint $table) {
            $table->index('batch_id', 'stock_issuance_batches_batch_id_index');
        });

        Schema::table('stock_issuance_items', function (Blueprint $table) {
            $table->index('stock_issuance_id', 'stock_issuance_items_stock_issuance_id_index');
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->index('requisition_id', 'approvals_requisition_id_index');
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->index('batch_id', 'inventory_transactions_batch_id_index');
            $table->index('user_id', 'inventory_transactions_user_id_index');
        });

        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->index('status', 'stock_receipts_status_index');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->index(['is_active', 'stock_status'], 'inventory_items_active_stock_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropIndex('inventory_batches_item_remaining_expiry_received_index');
            $table->dropIndex('inventory_batches_stock_receipt_id_index');
            $table->dropIndex('inventory_batches_storage_location_id_index');
        });

        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropIndex('requisitions_status_submitted_at_index');
        });

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->dropIndex('requisition_items_requisition_id_index');
            $table->dropIndex('requisition_items_item_outstanding_index');
        });

        Schema::table('stock_issuance_batches', function (Blueprint $table) {
            $table->dropIndex('stock_issuance_batches_batch_id_index');
        });

        Schema::table('stock_issuance_items', function (Blueprint $table) {
            $table->dropIndex('stock_issuance_items_stock_issuance_id_index');
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->dropIndex('approvals_requisition_id_index');
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('inventory_transactions_batch_id_index');
            $table->dropIndex('inventory_transactions_user_id_index');
        });

        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->dropIndex('stock_receipts_status_index');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex('inventory_items_active_stock_status_index');
        });
    }
};
