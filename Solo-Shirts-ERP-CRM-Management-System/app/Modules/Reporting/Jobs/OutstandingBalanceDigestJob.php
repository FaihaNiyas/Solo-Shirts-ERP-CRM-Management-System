<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Jobs;

use App\Modules\Finance\Models\CreditNote;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Weekly digest of each branch's outstanding receivables (invoiced − collected −
 * credited), recorded on the activity log for the finance team.
 */
final class OutstandingBalanceDigestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        Branch::query()->each(function (Branch $branch): void {
            $invoiced = (int) Invoice::query()->withoutGlobalScopes()
                ->where('branch_id', $branch->id)->sum('total_paise');
            $collected = (int) Payment::query()->withoutGlobalScopes()
                ->where('branch_id', $branch->id)->sum('amount_paise');
            $credited = (int) CreditNote::query()->withoutGlobalScopes()
                ->where('branch_id', $branch->id)->sum('total_paise');

            activity('reporting')
                ->event('outstanding-balance-digest')
                ->withProperties([
                    'branch_id' => $branch->id,
                    'outstanding_paise' => $invoiced - $collected - $credited,
                ])
                ->log("outstanding balance digest for branch {$branch->id}");
        });
    }
}
