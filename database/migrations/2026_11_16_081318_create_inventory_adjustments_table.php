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
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->integer('batch_id');
            $table->integer('user_id');
            $table->integer('approved_by');
            $table->integer('previous_qty'); // old qty
            $table->integer('changed_qty'); // difference qty  i. e. +5, -5
            $table->integer('new_qty'); // new total
            $table->string('reason');
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('inventory_batches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
