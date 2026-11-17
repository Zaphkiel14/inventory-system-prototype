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
            $table->integer('stock_issuance_item_id');
            $table->integer('batch_id');
            $table->integer('quantity');

            $table->timestamps();

            $table->foreign('stock_issuance_item_id')->references('id')->on('stock_issuance_items');
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
