<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Requisitions flow through three separate stages — request, approval and
 * issuance — and every quantity along the way is kept on the line item:
 *
 *   requested_qty    what the requester asked for
 *   available_qty    stock on hand when the request was submitted (snapshot)
 *   approved_qty     what the approver decided to dispense (null = undecided)
 *   released_qty     what has actually been issued so far
 *   outstanding_qty  approved but not yet released (approved - released)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->unique('request_number');
            $table->unsignedBigInteger('department_id')->nullable()->after('request_number');
            $table->unsignedBigInteger('section_id')->nullable()->after('department_id');
            $table->unsignedBigInteger('unit_id')->nullable()->after('section_id');
            $table->dateTime('cancelled_at')->nullable()->after('submitted_at');
        });

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->integer('available_qty')->default(0)->after('requested_qty');
            $table->integer('approved_qty')->nullable()->change();
            $table->integer('released_qty')->default(0)->change();
            $table->integer('outstanding_qty')->default(0)->after('released_qty');
        });
    }

    public function down(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            $table->dropColumn(['available_qty', 'outstanding_qty']);
        });

        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropUnique(['request_number']);
            $table->dropColumn(['department_id', 'section_id', 'unit_id', 'cancelled_at']);
        });
    }
};
