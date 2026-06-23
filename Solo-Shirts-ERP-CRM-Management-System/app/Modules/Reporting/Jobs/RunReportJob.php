<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Jobs;

use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Reporting\Services\ReportRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs one queued report to completion. Resolving + status transitions live in
 * ReportRunner::run, which records succeeded (+document) or failed (+error).
 */
final class RunReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $reportJobId) {}

    public function handle(ReportRunner $runner): void
    {
        $job = ReportJob::query()->withoutGlobalScopes()->find($this->reportJobId);

        if ($job !== null) {
            $runner->run($job);
        }
    }
}
