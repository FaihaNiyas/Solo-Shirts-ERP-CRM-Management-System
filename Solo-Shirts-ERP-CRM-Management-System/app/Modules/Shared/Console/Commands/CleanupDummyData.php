<?php

declare(strict_types=1);

namespace App\Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Auditable, scoped cleanup of dummy/demo transactional ERP data for a LOCAL
 * development database. This is intentionally NOT a database reset:
 *
 *  - It never runs migrate:fresh / db:wipe / blanket TRUNCATE.
 *  - It deletes an explicit, ordered list of transactional tables only.
 *  - It NEVER touches users, roles, permissions, branches, sequences, master
 *    config, suppliers, fabric_rolls, activity_log, login_attempts or
 *    personal_access_tokens.
 *  - It refuses to run outside the `local` environment.
 *  - It requires an existing, non-empty backup file before any deletion.
 *
 * The only DELETE-blocking trigger is `payments_no_delete` (BEFORE DELETE on
 * payments). Because DROP/CREATE TRIGGER is DDL and auto-commits in MySQL it
 * cannot live inside the row transaction; we drop it before the transaction and
 * recreate it in a `finally` block, then assert it is back.
 */
final class CleanupDummyData extends Command
{
    protected $signature = 'solo:cleanup-dummy-data
        {--dry-run : Report what would be deleted; change nothing}
        {--confirm : Actually perform the cleanup (requires --backup)}
        {--with-customers : Also delete customers, family members and measurement data (Group 2)}
        {--purge-orphan-docs : Also delete document files that have no matching documents row}
        {--backup= : Path to a completed, non-empty SQL backup file (required with --confirm)}';

    protected $description = 'Safely remove dummy/demo transactional ERP data (local only). Audit with --dry-run.';

    /** The exact DDL captured from the live trigger, recreated verbatim after deletion. */
    private const PAYMENTS_TRIGGER_SQL =
        "CREATE TRIGGER payments_no_delete BEFORE DELETE ON payments "
        . "FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'payments is append-only'";

    /** Append-only DELETE guard on payment_allocations (Phase 1), dropped/recreated like payments. */
    private const PAYMENT_ALLOCATIONS_TRIGGER_SQL =
        "CREATE TRIGGER payment_allocations_no_delete BEFORE DELETE ON payment_allocations "
        . "FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'payment_allocations is append-only'";

    /**
     * Group 3 transactional tables in strict child -> parent delete order.
     * FK-null steps and the payments trigger are interleaved in execute().
     * payment_allocations + pickup batch tables lead because they RESTRICT-reference
     * payments/invoices/orders and would otherwise block their deletion.
     */
    private const GROUP3 = [
        'payment_allocations', 'pickup_batch_items', 'pickup_batches',
        'documents', 'report_jobs', 'idempotency_keys', 'notifications',
        'delivery_otps', 'delivery_attempts', 'deliveries', 'rack_assignments',
        'invoice_lines', 'credit_notes', 'payments', 'invoices',
        'qc_defect_photos', 'qc_defects', 'qc_inspections', 'packing_checklists',
        'damage_report_photos', 'damage_reports', 'fabric_allocations',
        'production_transitions', 'tailor_assignments', 'tailor_daily_stats',
        'cut_bundles', 'print_logs', 'order_whatsapp_notifications',
        'alteration_status_logs', 'alteration_requests', 'measurement_alerts',
        'order_items', 'production_boxes', 'front_desk_order_drafts', 'orders',
        'daily_branch_stats',
    ];

    /** Group 2 customer/measurement tables, child -> parent. Only with --with-customers. */
    private const GROUP2 = [
        'measurement_versions', 'measurement_profiles', 'family_members', 'customers',
    ];

    /** Tables that must never be touched. Shown in the report for transparency. */
    private const PRESERVE = [
        'users', 'roles', 'permissions', 'role_has_permissions', 'model_has_roles',
        'model_has_permissions', 'password_reset_tokens', 'branches',
        'defect_categories', 'fabric_types', 'rack_slots', 'order_sequences',
        'invoice_sequences', 'customer_sequences', 'credit_note_sequences',
        'migrations', 'suppliers', 'fabric_rolls', 'purchase_orders',
        'purchase_order_items', 'grn', 'grn_items', 'activity_log',
        'login_attempts', 'personal_access_tokens',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $confirm = (bool) $this->option('confirm');
        $withCustomers = (bool) $this->option('with-customers');
        $purgeOrphans = (bool) $this->option('purge-orphan-docs');

        if ($dryRun === $confirm) {
            $this->error('Choose exactly one mode: --dry-run OR --confirm.');

            return self::FAILURE;
        }

        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        $backupPath = (string) $this->option('backup');
        if ($confirm && ! $this->guardBackup($backupPath)) {
            return self::FAILURE;
        }

        $this->printPlan($withCustomers, $purgeOrphans, $backupPath, $dryRun);

        if ($dryRun) {
            $this->newLine();
            $this->info('DRY RUN — nothing was changed. Re-run with --confirm --backup=<path> to execute.');

            return self::SUCCESS;
        }

        return $this->performCleanup($withCustomers, $purgeOrphans);
    }

    /** Refuse anywhere that is not a clearly-local, non-production database. */
    private function guardEnvironment(): bool
    {
        $env = app()->environment();
        $connection = config('database.default');
        $cfg = config("database.connections.{$connection}", []);
        $host = (string) ($cfg['host'] ?? '');
        $database = (string) ($cfg['database'] ?? '');

        $this->line("Environment : <comment>{$env}</comment>");
        $this->line("Connection  : <comment>{$connection}</comment>");
        $this->line("Host        : <comment>{$host}</comment>");
        $this->line("Database    : <comment>{$database}</comment>");
        $this->newLine();

        if ($env !== 'local') {
            $this->error("Refusing to run: APP_ENV is '{$env}', not 'local'.");

            return false;
        }

        $looksProd = str_contains(strtolower($database), 'prod')
            || str_contains(strtolower($host), 'prod')
            || ! in_array($host, ['127.0.0.1', 'localhost', '::1', ''], true);

        if ($looksProd) {
            $this->error("Refusing to run: connection looks like a remote/production target ({$host}/{$database}).");

            return false;
        }

        return true;
    }

    /** A real, non-empty backup file must exist before we delete anything. */
    private function guardBackup(string $path): bool
    {
        if ($path === '') {
            $this->error('--confirm requires --backup=<path> to a completed SQL dump. Aborting (backup missing).');

            return false;
        }

        if (! is_file($path)) {
            $this->error("Backup file not found at: {$path}. Aborting (backup failed).");

            return false;
        }

        if ((int) filesize($path) <= 0) {
            $this->error("Backup file is empty (0 bytes): {$path}. Aborting (backup failed).");

            return false;
        }

        $this->info('Backup verified: ' . $path . ' (' . number_format((float) filesize($path)) . ' bytes).');
        $this->newLine();

        return true;
    }

    private function printPlan(bool $withCustomers, bool $purgeOrphans, string $backupPath, bool $dryRun): void
    {
        $targets = self::GROUP3;
        if ($withCustomers) {
            $targets = array_merge($targets, self::GROUP2);
        }

        $rows = [];
        $total = 0;
        foreach ($targets as $table) {
            $count = DB::table($table)->count();
            $total += $count;
            $rows[] = [$table, number_format($count), in_array($table, self::GROUP2, true) ? 'Group 2' : 'Group 3'];
        }

        $this->info('Tables proposed for cleanup (delete order = top to bottom):');
        $this->table(['Table', 'Rows to delete', 'Group'], $rows);
        $this->line("Total rows to delete: <comment>" . number_format($total) . "</comment>");
        $this->newLine();

        // FK-null operations (master rows preserved; only occupancy reset).
        $rackOccupied = DB::table('rack_slots')->whereNotNull('current_order_item_id')->count();
        $boxOccupied = DB::table('production_boxes')->whereNotNull('current_order_item_id')->count();
        $this->info('FK reset (UPDATE, rows preserved):');
        $this->line("  rack_slots.current_order_item_id -> NULL on {$rackOccupied} occupied slot(s) (62 master slots preserved)");
        $this->line("  production_boxes.current_order_item_id -> NULL on {$boxOccupied} box(es) before delete");
        $this->newLine();

        // Files.
        $docFiles = DB::table('documents')->count();
        $this->info('Files to delete:');
        $this->line("  {$docFiles} file(s) referenced by deleted documents rows");
        if ($purgeOrphans) {
            $orphans = $this->orphanDocFiles();
            $this->line('  ' . count($orphans) . ' orphan document file(s) with no DB row (--purge-orphan-docs)');
        } else {
            $this->line('  orphan document files: NOT purged (pass --purge-orphan-docs to include)');
        }
        $this->newLine();

        // Trigger plan.
        $this->info('Trigger handling:');
        $this->line('  DROP payments_no_delete -> delete payments inside txn -> recreate in finally -> assert restored');
        $this->newLine();

        // Preserved tables.
        $preserveRows = [];
        foreach (self::PRESERVE as $table) {
            $preserveRows[] = [$table, number_format(DB::table($table)->count())];
        }
        $this->info('Preserved tables (never touched):');
        $this->table(['Table', 'Rows (unchanged)'], $preserveRows);

        $this->line('Backup path : <comment>' . ($backupPath !== '' ? $backupPath : ($dryRun ? '(supply with --backup on --confirm)' : 'MISSING')) . '</comment>');
    }

    private function performCleanup(bool $withCustomers, bool $purgeOrphans): int
    {
        // Capture the document file paths before their rows disappear.
        $docFiles = DB::table('documents')->get(['disk', 'path'])
            ->map(fn ($d) => ['disk' => $d->disk, 'path' => $d->path])->all();

        $deleted = [];

        // Trigger drop must be outside the transaction (DDL auto-commits).
        DB::unprepared('DROP TRIGGER IF EXISTS payments_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS payment_allocations_no_delete');

        try {
            DB::transaction(function () use ($withCustomers, &$deleted): void {
                foreach (self::GROUP3 as $table) {
                    // Break the two FK cycles just before the dependent delete.
                    if ($table === 'invoice_lines') {
                        DB::table('rack_slots')->whereNotNull('current_order_item_id')
                            ->update(['current_order_item_id' => null]);
                    }
                    if ($table === 'order_items') {
                        DB::table('production_boxes')->whereNotNull('current_order_item_id')
                            ->update(['current_order_item_id' => null]);
                    }

                    $deleted[$table] = DB::table($table)->delete();
                }

                if ($withCustomers) {
                    foreach (self::GROUP2 as $table) {
                        $deleted[$table] = DB::table($table)->delete();
                    }
                }
            });
        } finally {
            // Always restore the append-only guards, even on rollback.
            DB::unprepared('DROP TRIGGER IF EXISTS payments_no_delete');
            DB::unprepared(self::PAYMENTS_TRIGGER_SQL);
            DB::unprepared('DROP TRIGGER IF EXISTS payment_allocations_no_delete');
            DB::unprepared(self::PAYMENT_ALLOCATIONS_TRIGGER_SQL);
        }

        // Verify the guards are back before reporting success.
        $triggers = collect(DB::select('SHOW TRIGGERS'))->pluck('Trigger');
        foreach (['payments_no_delete', 'payment_allocations_no_delete'] as $name) {
            if (! $triggers->contains($name)) {
                $this->error("CRITICAL: {$name} trigger was NOT restored. Investigate immediately.");

                return self::FAILURE;
            }
        }

        // Files are not transactional — delete after the rows are gone.
        $filesDeleted = 0;
        foreach ($docFiles as $f) {
            if (Storage::disk($f['disk'])->exists($f['path'])) {
                Storage::disk($f['disk'])->delete($f['path']);
                $filesDeleted++;
            }
        }

        $orphansDeleted = 0;
        if ($purgeOrphans) {
            foreach ($this->orphanDocFiles() as $path) {
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                    $orphansDeleted++;
                }
            }
        }

        // Results.
        $rows = [];
        $total = 0;
        foreach ($deleted as $table => $n) {
            $total += $n;
            $rows[] = [$table, number_format($n)];
        }
        $this->newLine();
        $this->info('Cleanup complete.');
        $this->table(['Table', 'Rows deleted'], $rows);
        $this->line('Total rows deleted     : <comment>' . number_format($total) . '</comment>');
        $this->line('Referenced files deleted: <comment>' . $filesDeleted . '</comment>');
        $this->line('Orphan files deleted    : <comment>' . $orphansDeleted . '</comment>');
        $this->line('payments_no_delete      : <info>restored</info>');
        $rackReset = DB::table('rack_slots')->whereNotNull('current_order_item_id')->count();
        $this->line("rack_slots still occupied: <comment>{$rackReset}</comment> (expected 0)");

        return self::SUCCESS;
    }

    /** Files under documents/* on the local disk that have no matching documents row. */
    private function orphanDocFiles(): array
    {
        $known = DB::table('documents')->where('disk', 'local')->pluck('path')->all();
        $known = array_flip($known);

        return array_values(array_filter(
            Storage::disk('local')->allFiles('documents'),
            fn ($path) => ! isset($known[$path]),
        ));
    }
}
