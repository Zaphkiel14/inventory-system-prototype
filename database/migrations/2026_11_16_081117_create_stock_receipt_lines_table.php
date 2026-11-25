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
        Schema::create('stock_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_receipt_id')->constrained('stock_receipts');
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->unsignedInteger('ordered_qty');
            $table->timestamps();

            $table->unique(['stock_receipt_id', 'inventory_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_receipt_lines');
    }
};
