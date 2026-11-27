<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per physical property unit released at issuance, tied to the batch
 * draw it came from. Unit cost is copied from the batch and decides the class.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('issued_property_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_issuance_batch_id')->constrained('stock_issuance_batches');
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('unit_cost', 12, 2);
            $table->string('property_class');
            $table->timestamps();

            // A serial is unique per item; NULL serials don't collide.
            $table->unique(['inventory_item_id', 'serial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issued_property_units');
    }
};
