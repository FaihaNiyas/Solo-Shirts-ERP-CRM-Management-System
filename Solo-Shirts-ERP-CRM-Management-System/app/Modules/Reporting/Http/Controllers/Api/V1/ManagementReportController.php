<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Reporting\Services\ManagementReportService;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Phase 9 — read-only management reports. Every endpoint is gated by reports.view
 * (Front Desk has neither reports.view nor dashboard.view, so it is blocked) and
 * branch-scoped: staff are pinned to their own branch by BranchContext; only an
 * Owner (branch context null) may pass ?branch_id to focus a branch or omit it to
 * see all branches. Nothing here mutates data.
 */
final class ManagementReportController extends BaseApiController
{
    public function __construct(private readonly ManagementReportService $reports) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);
        [$from, $to] = $this->range($request);

        return $this->respond($this->reports->dashboard($this->branchId($request), $from, $to));
    }

    public function ordersDaily(Request $request): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);
        [$from, $to] = $this->range($request);
        $lifecycle = $request->filled('lifecycle_status') ? (string) $request->string('lifecycle_status') : null;

        return $this->respond([
            'date_range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'rows' => $this->reports->ordersDaily($this->branchId($request), $from, $to, $lifecycle),
        ]);
    }

    public function paymentsPending(Request $request): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);

        return $this->respond(['rows' => $this->reports->pendingPayments($this->branchId($request))]);
    }

    public function productionStages(Request $request): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);

        return $this->respond(['rows' => $this->reports->productionStages($this->branchId($request))]);
    }

    public function damage(Request $request): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);
        [$from, $to] = $this->range($request);

        return $this->respond($this->reports->damage($this->branchId($request), $from, $to));
    }

    public function salesGst(Request $request): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);
        [$from, $to] = $this->range($request);

        return $this->respond($this->reports->salesGst($this->branchId($request), $from, $to));
    }

    public function inventoryStock(Request $request): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);

        return $this->respond($this->reports->inventoryStock($this->branchId($request)));
    }

    public function purchases(Request $request): JsonResponse
    {
        $this->authorize('viewReports', ReportJob::class);
        [$from, $to] = $this->range($request);

        return $this->respond($this->reports->purchases($this->branchId($request), $from, $to));
    }

    /**
     * The branch to report on: staff are pinned to their own branch; an Owner
     * (context null) may focus a branch via ?branch_id or omit it for all branches.
     */
    private function branchId(Request $request): ?int
    {
        $context = app(BranchContext::class)->current();

        if ($context !== null) {
            return $context;
        }

        return $request->filled('branch_id') ? $request->integer('branch_id') : null;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $to = $request->filled('to') ? Carbon::parse((string) $request->string('to')) : Carbon::today();
        $from = $request->filled('from') ? Carbon::parse((string) $request->string('from')) : $to->copy()->subDays(29);

        return [$from, $to];
    }
}
