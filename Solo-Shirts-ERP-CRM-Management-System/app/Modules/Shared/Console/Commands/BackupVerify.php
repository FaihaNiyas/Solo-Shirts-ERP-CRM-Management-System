<?php

declare(strict_types=1);

namespace App\Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Restore-drill sanity check. In production this runs against a temp database
 * restored from the latest dump; here it runs against the connected database and
 * asserts core invariants hold. A non-zero exit signals a bad/incomplete restore
 * and should page on-call (see the DR runbook in the README).
 */
final class BackupVerify extends Command
{
    protected $signature = 'backup:verify';

    protected $description = 'Run restore-drill sanity checks against the database.';

    public function handle(): int
    {
        $failures = [];

        if (DB::table('customers')->count() === 0) {
            $failures[] = 'no customers present (empty or incomplete restore)';
        }

        if (DB::table('fabric_rolls')->where('remaining_metres', '<', 0)->exists()) {
            $failures[] = 'negative fabric stock detected';
        }

        $orphanInvoices = DB::table('invoices')
            ->whereNotIn('order_id', DB::table('orders')->select('id'))
            ->exists();

        if ($orphanInvoices) {
            $failures[] = 'orphan invoices reference missing orders';
        }

        if ($failures !== []) {
            foreach ($failures as $failure) {
                $this->error("FAIL: {$failure}");
            }

            return self::FAILURE;
        }

        $this->info('Backup verification passed: all invariants hold.');

        return self::SUCCESS;
    }
}
