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
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->string('type')->default('count_correction')->after('user_id');
            $table->string('requested_by')->default('')->after('type');
            $table->string('approved_by')->change();
            $table->text('reason')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropColumn(['type', 'requested_by']);
            $table->string('reason')->change();
        });
    }
};
