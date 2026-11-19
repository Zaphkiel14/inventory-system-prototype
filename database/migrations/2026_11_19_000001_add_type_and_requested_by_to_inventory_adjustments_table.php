<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        // History is append-only: block UPDATE / DELETE at the database level too,
        // so even raw queries that bypass the Eloquent model can't rewrite it.
        match (DB::getDriverName()) {
            'sqlite' => $this->sqliteTriggers(),
            'mysql', 'mariadb' => $this->mysqlTriggers(),
            default => null,
        };
    }

    private function sqliteTriggers(): void
    {
        DB::unprepared("
            CREATE TRIGGER inventory_adjustments_no_update BEFORE UPDATE ON inventory_adjustments
            BEGIN SELECT RAISE(ABORT, 'Inventory adjustments are permanent and cannot be modified.'); END;
        ");
        DB::unprepared("
            CREATE TRIGGER inventory_adjustments_no_delete BEFORE DELETE ON inventory_adjustments
            BEGIN SELECT RAISE(ABORT, 'Inventory adjustments are permanent and cannot be deleted.'); END;
        ");
    }

    private function mysqlTriggers(): void
    {
        DB::unprepared("
            CREATE TRIGGER inventory_adjustments_no_update BEFORE UPDATE ON inventory_adjustments
            FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inventory adjustments are permanent and cannot be modified.'
        ");
        DB::unprepared("
            CREATE TRIGGER inventory_adjustments_no_delete BEFORE DELETE ON inventory_adjustments
            FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inventory adjustments are permanent and cannot be deleted.'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS inventory_adjustments_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS inventory_adjustments_no_delete');

        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropColumn(['type', 'requested_by']);
            $table->string('reason')->change();
        });
    }
};
