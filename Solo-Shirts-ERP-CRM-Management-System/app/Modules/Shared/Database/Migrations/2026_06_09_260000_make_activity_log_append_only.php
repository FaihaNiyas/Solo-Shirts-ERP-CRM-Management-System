<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The audit trail must be tamper-evident: once written, an activity_log row can
 * never be edited. A BEFORE UPDATE trigger enforces this in every environment
 * (the production grant policy additionally restricts the app user to
 * SELECT/INSERT). DELETE is intentionally left to the migration user so the
 * activitylog:clean retention command can still prune old rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(
            'CREATE TRIGGER activity_log_no_update BEFORE UPDATE ON activity_log '
            . "FOR EACH ROW SIGNAL SQLSTATE '45000' "
            . "SET MESSAGE_TEXT = 'activity_log is append-only';"
        );
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS activity_log_no_update;');
    }
};
