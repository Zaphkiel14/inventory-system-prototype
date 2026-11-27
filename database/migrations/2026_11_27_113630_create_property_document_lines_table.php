<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lines of an ICS or PAR. A line usually points at one issued property unit
 * (each unit lands on at most one document); hand-encoded lines for existing
 * property have no unit. Total cost is computed, never stored.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_document_lines', function (Blueprint $table) {
            $table->id();
            // Cascades for drafts; PropertyDocuments' deleting hook refuses finalized documents.
            $table->foreignId('property_document_id')->constrained('property_documents')->cascadeOnDelete();
            $table->foreignId('issued_property_unit_id')->nullable()->unique()->constrained('issued_property_units');
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_cost', 12, 2);
            $table->string('item_name');
            $table->string('item_type')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            // Unique across every ICS and PAR line; NULL until the user enters it.
            $table->string('property_number')->nullable()->unique();
            $table->unsignedTinyInteger('estimated_useful_life_years')->nullable();
            $table->date('date_acquired')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_document_lines');
    }
};
