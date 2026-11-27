<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One property tag per ICS/PAR line that gets tagged. The printed content is
 * read live from the line and its document, never copied here.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_document_line_id')->unique()->constrained('property_document_lines');
            $table->string('status')->default('pending');
            $table->unsignedInteger('print_count')->default(0);
            $table->dateTime('last_printed_at')->nullable();
            $table->foreignId('last_printed_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_tags');
    }
};
