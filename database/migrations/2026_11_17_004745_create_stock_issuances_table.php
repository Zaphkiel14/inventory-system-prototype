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
            // The signed-in user who recorded the release (released_by keeps the printed name).
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('issuance_number')->unique();
            $table->string('released_by');
            $table->string('received_by');
            $table->dateTime('released_at');
            $table->text('remarks')->nullable();
            $table->timestamps();
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
