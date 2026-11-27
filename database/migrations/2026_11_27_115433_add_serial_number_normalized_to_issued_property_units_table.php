<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Serial numbers are unique per item regardless of case or surrounding
 * spaces, enforced by the database: a lowercased, trimmed copy carries the
 * unique index, while serial_number keeps the value as entered.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('issued_property_units', function (Blueprint $table) {
            $table->string('serial_number_normalized')->nullable()->after('serial_number');
        });

        DB::table('issued_property_units')
            ->whereNotNull('serial_number')
            ->update(['serial_number_normalized' => DB::raw('lower(trim(serial_number))')]);

        Schema::table('issued_property_units', function (Blueprint $table) {
            $table->dropUnique(['inventory_item_id', 'serial_number']);
            $table->unique(['inventory_item_id', 'serial_number_normalized']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issued_property_units', function (Blueprint $table) {
            $table->dropUnique(['inventory_item_id', 'serial_number_normalized']);
            $table->unique(['inventory_item_id', 'serial_number']);
            $table->dropColumn('serial_number_normalized');
        });
    }
};
