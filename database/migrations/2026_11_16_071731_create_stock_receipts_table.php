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
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('purchase_order_number')->nullable();
            $table->string('received_by');
            $table->string('reference_number');
            $table->dateTime('received_date');
            $table->dateTime('delivery_date');
            $table->string('status');
            $table->string('remarks')->nullable();
            $table->foreignId('parent_receipt_id')->nullable()->constrained('stock_receipts');
            $table->boolean('is_donation')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->text('closed_reason')->nullable();
            $table->timestamps();
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
