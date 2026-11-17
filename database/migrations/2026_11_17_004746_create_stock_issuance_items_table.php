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
        Schema::create('stock_issuance_items', function (Blueprint $table) {
            $table->id();
            $table->integer('stock_issuance_id');
            $table->integer('requisition_item_id');
            $table->integer('released_qty');
            $table->timestamps();

            $table->foreign('stock_issuance_id')->references('id')->on('stock_issuances');
            $table->foreign('requisition_item_id')->references('id')->on('requisition_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_issuance_items');
    }
};
