<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signatory designations for the RIS Issued by / Received by blocks. Written
 * only on create, as issuances are append-only.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_issuances', function (Blueprint $table) {
            $table->string('released_by_designation')->nullable();
            $table->string('received_by_designation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_issuances', function (Blueprint $table) {
            $table->dropColumn(['released_by_designation', 'received_by_designation']);
        });
    }
};
