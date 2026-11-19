<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_issuances', function (Blueprint $table) {
            $table->unique('issuance_number');
            // The signed-in user who recorded the release (released_by keeps the printed name).
            $table->foreignId('user_id')->nullable()->after('requisition_id')->constrained('users');
            $table->text('remarks')->nullable()->after('released_at');
        });
    }

    public function down(): void
    {
        Schema::table('stock_issuances', function (Blueprint $table) {
            $table->dropUnique(['issuance_number']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('remarks');
        });
    }
};
