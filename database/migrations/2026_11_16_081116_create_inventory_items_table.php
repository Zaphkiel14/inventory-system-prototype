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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->integer('inventory_category_id');
            $table->string('item_code');
            $table->string('name');
            $table->string('unit');
            $table->string('allocation_method');
            $table->boolean('requires_expiry');
            $table->integer('minimum_stock');
            $table->integer('reorder_level');
            $table->boolean('is_active')->default('1');
            $table->timestamps();

            $table->foreign('inventory_category_id')->references('id')->on('inventory_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
