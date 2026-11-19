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
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->foreignId('stock_receipt_id')->constrained('stock_receipts');
            $table->foreignId('storage_location_id')->constrained('storage_locations');
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->integer('received_qty');
            $table->integer('remaining_qty');
            $table->date('manufactured_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->dateTime('received_at');
            $table->decimal('unit_cost', 12, 2);
            $table->timestamps();
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
