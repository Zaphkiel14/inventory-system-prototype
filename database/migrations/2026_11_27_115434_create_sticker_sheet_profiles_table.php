<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sticker sheet layouts for property tags, all in millimetres. Offsets are
 * printer calibration and may be negative. One profile is the default.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sticker_sheet_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('page_width_mm', 6, 2);
            $table->decimal('page_height_mm', 6, 2);
            $table->unsignedTinyInteger('columns');
            $table->unsignedTinyInteger('rows');
            $table->decimal('tag_width_mm', 6, 2);
            $table->decimal('tag_height_mm', 6, 2);
            $table->decimal('margin_top_mm', 6, 2)->default(0);
            $table->decimal('margin_left_mm', 6, 2)->default(0);
            $table->decimal('gap_x_mm', 6, 2)->default(0);
            $table->decimal('gap_y_mm', 6, 2)->default(0);
            $table->decimal('offset_x_mm', 6, 2)->default(0);
            $table->decimal('offset_y_mm', 6, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Every environment (including production, which isn't seeded) starts
        // with the A4 2 × 5 default the hospital prints on.
        DB::table('sticker_sheet_profiles')->insert([
            'name' => 'A4 · 2 × 5',
            'page_width_mm' => 210,
            'page_height_mm' => 297,
            'columns' => 2,
            'rows' => 5,
            'tag_width_mm' => 95,
            'tag_height_mm' => 54,
            'margin_top_mm' => 9.5,
            'margin_left_mm' => 7.5,
            'gap_x_mm' => 5,
            'gap_y_mm' => 2,
            'offset_x_mm' => 0,
            'offset_y_mm' => 0,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sticker_sheet_profiles');
    }
};
