<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the Monitoring pages: the ledger is filtered by date and ordered
 * by (created_at, id), searched by its source document, and read per item;
 * batches are filtered by expiry and remaining stock.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->index(['created_at', 'id'], 'inventory_transactions_created_at_id_index');
            $table->index(['reference_type', 'reference_id'], 'inventory_transactions_reference_index');
            $table->index(['inventory_item_id', 'created_at', 'id'], 'inventory_transactions_item_created_at_id_index');
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->index(['expiration_date', 'remaining_qty'], 'inventory_batches_expiration_remaining_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('inventory_transactions_created_at_id_index');
            $table->dropIndex('inventory_transactions_reference_index');
            $table->dropIndex('inventory_transactions_item_created_at_id_index');
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropIndex('inventory_batches_expiration_remaining_index');
        });
    }
};
