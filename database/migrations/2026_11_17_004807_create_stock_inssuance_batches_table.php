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
        Schema::create('stock_inssuance_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_issuance_item_id')->constrained('stock_issuance_items');
            $table->foreignId('batch_id')->constrained('inventory_batches');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_inssuance_batches');
    }
};
