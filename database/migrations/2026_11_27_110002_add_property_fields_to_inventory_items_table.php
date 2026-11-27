<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flags equipment and semi-expendables as property (ICS/PAR), with a default
 * estimated useful life for their document lines.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->boolean('is_property')->default(false);
            $table->unsignedTinyInteger('default_useful_life_years')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['is_property', 'default_useful_life_years']);
        });
    }
};
