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
        Schema::create('stock_issuances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('requisitions'); // FK
            $table->string('issuance_number'); // unique code
            $table->string('released_by');
            $table->string('received_by');
            $table->dateTime('released_at');
            $table->timestamps();

            $table->foreign('requisition_id')->references('id')->on('requisitions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_issuances');
    }
};
