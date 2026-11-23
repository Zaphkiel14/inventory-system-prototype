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
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('requisitions');
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->integer('requested_qty');
            $table->integer('available_qty')->default(0);
            $table->integer('approved_qty')->nullable();
            $table->integer('released_qty')->default(0);
            $table->integer('outstanding_qty')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_items');
    }
};
