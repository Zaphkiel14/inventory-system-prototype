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
        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->integer('requisition_id');
            $table->integer('inventory_item_id');
            $table->integer('requested_qty');
            $table->integer('approved_qty');
            $table->integer('released_qty');
            $table->timestamps();

            $table->foreign('requisition_id')->references('id')->on('requisitions');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_items');
    }
};
