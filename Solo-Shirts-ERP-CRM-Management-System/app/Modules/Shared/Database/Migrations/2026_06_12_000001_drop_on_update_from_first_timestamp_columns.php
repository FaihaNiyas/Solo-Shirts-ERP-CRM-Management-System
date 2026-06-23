<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hardening follow-up to QA-001. Each table below declared a domain timestamp
 * as its first `TIMESTAMP NOT NULL` column, so MySQL/MariaDB (with
 * explicit_defaults_for_timestamp = 0) silently attached
 * `ON UPDATE CURRENT_TIMESTAMP`. Any later UPDATE to the row would then reset
 * that "when did X happen" timestamp to now — corrupting the audit time
 * (e.g. a reassigned tailor_assignment, a released rack_assignment, a
 * consumed fabric_allocation, a succeeded report_job).
 *
 * Unlike delivery_otps.expires_at (QA-001) these do not drive comparison logic,
 * so no test caught them — but they are a real data-integrity risk. DATETIME
 * carries no implicit ON UPDATE. ALTER ... MODIFY preserves existing values.
 *
 * @var array<int, array{0:string,1:string}> $columns table => column
 */
return new class extends Migration
{
    // Every first-`TIMESTAMP NOT NULL` domain column across the schema. Laravel
    // manages created_at/updated_at in PHP, so NO column in this app should
    // carry ON UPDATE CURRENT_TIMESTAMP — enforced by NoUnintendedOnUpdateTimestampsTest.
    // The highest-impact one is invoices.issued_at: invoices receive status
    // updates from the payment reconciler, which would otherwise reset the
    // issue date on every reconcile.
    /** @var list<array{table:string, column:string}> */
    private array $columns = [
        ['table' => 'fabric_allocations', 'column' => 'reserved_at'],
        ['table' => 'rack_assignments', 'column' => 'assigned_at'],
        ['table' => 'tailor_assignments', 'column' => 'assigned_at'],
        ['table' => 'report_jobs', 'column' => 'requested_at'],
        ['table' => 'delivery_attempts', 'column' => 'attempted_at'],
        ['table' => 'invoices', 'column' => 'issued_at'],
        ['table' => 'credit_notes', 'column' => 'issued_at'],
        ['table' => 'payments', 'column' => 'paid_at'],
        ['table' => 'documents', 'column' => 'generated_at'],
        ['table' => 'grn', 'column' => 'received_at'],
        ['table' => 'login_attempts', 'column' => 'attempted_at'],
        ['table' => 'qc_inspections', 'column' => 'inspected_at'],
        ['table' => 'fabric_movements', 'column' => 'occurred_at'],
        ['table' => 'production_transitions', 'column' => 'occurred_at'],
    ];

    public function up(): void
    {
        foreach ($this->columns as $c) {
            Schema::table($c['table'], function (Blueprint $table) use ($c) {
                $table->dateTime($c['column'])->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $c) {
            Schema::table($c['table'], function (Blueprint $table) use ($c) {
                $table->timestamp($c['column'])->nullable(false)->change();
            });
        }
    }
};
