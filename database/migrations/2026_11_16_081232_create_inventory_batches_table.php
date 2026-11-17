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
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->integer('inventory_item_id');
            $table->integer('stock_receipt_id');
            $table->integer('storage_location_id');
            $table->string('batch_number');
            $table->integer('received_qty');
            $table->integer('remaining_qty');
            $table->date('manufactured_date');
            $table->date('expiration_date');
            $table->date('received_at');
            $table->float('unit_cost', 2);
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
            $table->foreign('stock_receipt_id')->references('id')->on('stock_receipts');
            $table->foreign('storage_location_id')->references('id')->on('storage_location');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
