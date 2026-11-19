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
        Schema::create('stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->integer('supplier_id'); // FK
            $table->string('purchase_order_number');
            $table->string('received_by');
            $table->string('reference_number');
            $table->dateTime('received_date');
            $table->dateTime('delivery_date');
            $table->enum('status', ['complete_delivery', 'partial_delivery']);
            $table->string('remarks');
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_receipts');
    }
};
