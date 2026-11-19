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
            $table->foreignId('stock_issuance_id')->constrained('stock_issuances');
            $table->foreignId('requisition_item_id')->constrained('requisition_items');
            $table->integer('released_qty');
            $table->timestamps();
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
