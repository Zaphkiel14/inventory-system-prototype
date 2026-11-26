<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for BatchCodeGenerator::lastSequence(), which finds the highest
 * batch/lot number for a prefix with a range scan on these columns.
 * (request_number and issuance_number already have unique indexes.)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->index('batch_number', 'inventory_batches_batch_number_index');
            $table->index('lot_number', 'inventory_batches_lot_number_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropIndex('inventory_batches_batch_number_index');
            $table->dropIndex('inventory_batches_lot_number_index');
        });
    }
};
