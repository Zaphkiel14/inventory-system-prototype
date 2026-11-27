<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RIS (Appendix 63) header fields on the requisition. Division and office may
 * be any administrative area; their labels are snapshotted alongside the ids.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->string('ris_number')->nullable()->unique();
            $table->string('fund_cluster')->nullable();
            $table->unsignedBigInteger('division_area_id')->nullable();
            $table->string('division')->nullable();
            $table->unsignedBigInteger('office_area_id')->nullable();
            $table->string('office')->nullable();
            $table->string('rc_code')->nullable();
            $table->string('requester_position')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->string('approved_by_designation')->nullable();
            $table->date('approved_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropUnique(['ris_number']);
            $table->dropColumn([
                'ris_number',
                'fund_cluster',
                'division_area_id',
                'division',
                'office_area_id',
                'office',
                'rc_code',
                'requester_position',
                'approved_by_name',
                'approved_by_designation',
                'approved_on',
            ]);
        });
    }
};
