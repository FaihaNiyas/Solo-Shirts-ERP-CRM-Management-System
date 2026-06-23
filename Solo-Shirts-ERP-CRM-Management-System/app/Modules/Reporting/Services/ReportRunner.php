<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Services;

use App\Models\User;
use App\Modules\Reporting\Exceptions\ReportingException;
use App\Modules\Reporting\Jobs\RunReportJob;
use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Reporting\Reports\DefectAnalyticsReport;
use App\Modules\Reporting\Reports\FabricConsumptionReport;
use App\Modules\Reporting\Reports\FinanceSummaryReport;
use App\Modules\Reporting\Reports\OrdersReport;
use App\Modules\Reporting\Reports\ProductionDailyCompletionReport;
use App\Modules\Reporting\Reports\ProductionDelayedReport;
use App\Modules\Reporting\Reports\ProductionQcFailReport;
use App\Modules\Reporting\Reports\ProductionReworkReport;
use App\Modules\Reporting\Reports\ProductionStagePendingReport;
use App\Modules\Reporting\Reports\ProductionSupervisorCompletedReport;
use App\Modules\Reporting\Reports\ReportInterface;
use App\Modules\Reporting\Reports\TailorPerformanceReport;
use Throwable;

/**
 * Registry + lifecycle for on-demand reports. Each request records a pending
 * ReportJob and dispatches the heavy work to the queue; running the job drives
 * pending → running → succeeded (with a document) or failed (with an error).
 */
final class ReportRunner
{
    /**
     * @var array<string, ReportInterface>
     */
    private array $reports = [];

    public function __construct(
        private readonly ReportDocumentWriter $writer,
        OrdersReport $orders,
        FinanceSummaryReport $finance,
        FabricConsumptionReport $fabric,
        TailorPerformanceReport $tailor,
        DefectAnalyticsReport $defects,
        // Phase G — production reports (kanban operations).
        ProductionStagePendingReport $stagePending,
        ProductionDelayedReport $delayed,
        ProductionReworkReport $rework,
        ProductionQcFailReport $qcFail,
        ProductionSupervisorCompletedReport $supervisorCompleted,
        ProductionDailyCompletionReport $dailyCompletion,
    ) {
        $registry = [
            $orders, $finance, $fabric, $tailor, $defects,
            $stagePending, $delayed, $rework, $qcFail, $supervisorCompleted, $dailyCompletion,
        ];

        foreach ($registry as $report) {
            $this->reports[$report->kind()] = $report;
        }
    }

    /**
     * @return list<string>
     */
    public function kinds(): array
    {
        return array_keys($this->reports);
    }

    public function handlerFor(string $kind): ReportInterface
    {
        return $this->reports[$kind] ?? throw ReportingException::unknownReport($kind);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function request(string $kind, array $params, User $actor): ReportJob
    {
        // Validate the kind up front (throws before any row is created).
        $this->handlerFor($kind);

        $job = ReportJob::query()->create([
            'branch_id' => $actor->branch_id,
            'kind' => $kind,
            'params' => $params,
            'status' => ReportJob::STATUS_PENDING,
            'requested_by' => $actor->id,
            'requested_at' => now(),
        ]);

        RunReportJob::dispatch($job->id);

        return $job;
    }

    public function run(ReportJob $job): void
    {
        $job->update(['status' => ReportJob::STATUS_RUNNING]);

        try {
            $report = $this->handlerFor($job->kind);
            $document = $this->writer->write($report, $job);

            $job->update([
                'status' => ReportJob::STATUS_SUCCEEDED,
                'document_id' => $document->id,
                'completed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            $job->update([
                'status' => ReportJob::STATUS_FAILED,
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}
