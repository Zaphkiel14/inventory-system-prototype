<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ICS (Appendix 59) and PAR (Appendix 71) custody documents share this
 * table. They never move stock; they record who is accountable for issued
 * property units.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_documents', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('ics_class')->nullable();
            // Typed by the user; empty on drafts. NULLs don't collide on the unique index.
            $table->string('document_number')->nullable();
            $table->string('fund_cluster')->nullable();
            $table->date('document_date');
            $table->foreignId('requisition_id')->nullable()->constrained('requisitions');
            $table->foreignId('stock_issuance_id')->nullable()->constrained('stock_issuances');
            $table->string('iar_number')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_position')->nullable();
            $table->date('from_date')->nullable();
            $table->string('to_name')->nullable();
            $table->string('to_position')->nullable();
            $table->date('to_date')->nullable();
            $table->string('status')->default('draft');
            $table->dateTime('finalized_at')->nullable();
            $table->date('renewal_due_on')->nullable();
            $table->timestamps();

            $table->unique(['type', 'document_number']);
            $table->index(['stock_issuance_id', 'type', 'ics_class']);
            $table->index(['status', 'renewal_due_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_documents');
    }
};
