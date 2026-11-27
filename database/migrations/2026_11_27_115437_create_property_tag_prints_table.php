<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only print log: one row per tag printed (first print or reprint
 * with a reason), and one row per blank or calibration sheet (no tag).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_tag_prints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_tag_id')->nullable()->constrained('property_tags');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('kind');
            $table->text('reason')->nullable();
            $table->foreignId('sticker_sheet_profile_id')->constrained('sticker_sheet_profiles');
            $table->dateTime('printed_at');
            $table->timestamps();

            $table->index(['property_tag_id', 'printed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_tag_prints');
    }
};
