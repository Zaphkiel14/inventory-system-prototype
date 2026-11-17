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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('inventory_item_id');
            $table->integer('batch_id');
            $table->integer('user_id');
            $table->integer('type');
            $table->integer('quantity');
            $table->date('balance_after');
            $table->date('reference_type');
            $table->date('reference_id');
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
            $table->foreign('batch_id')->references('id')->on('inventory_batches');
            $table->foreign('user_id')->references('id')->on('user_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
