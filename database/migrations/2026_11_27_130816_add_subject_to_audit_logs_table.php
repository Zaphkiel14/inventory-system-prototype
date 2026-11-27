<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What an audit row is about, captured when it is written: the record type
 * the Audit Logs filter uses ('ics', 'par', 'requisition') and its readable
 * label (e.g. "Line LV2026-0001 on ICS SPLV-2026-10-062"). Deleted drafts and
 * lines stay findable by type and number because nothing is looked up later.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('subject_type')->nullable()->index();
            $table->string('subject_label')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['subject_type']);
            $table->dropIndex(['subject_label']);
            $table->dropColumn(['subject_type', 'subject_label']);
        });
    }
};
