<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Finance\Models\CreditNote;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final class FinanceDashboardController extends BaseApiController
{
    public function summary(): JsonResponse
    {
        $this->authorize('viewDashboard', Invoice::class);

        $branchId = app(BranchContext::class)->current();
        $key = 'finance.dashboard.summary.' . ($branchId ?? 'all');

        $summary = Cache::remember($key, now()->addMinutes(5), static function (): array {
            $invoiced = (int) Invoice::query()->sum('total_paise');
            $collected = (int) Payment::query()->sum('amount_paise');
            $credited = (int) CreditNote::query()->sum('total_paise');

            return [
                'invoice_count' => Invoice::query()->count(),
                'invoiced_paise' => $invoiced,
                'collected_paise' => $collected,
                'credited_paise' => $credited,
                'outstanding_paise' => $invoiced - $collected - $credited,
            ];
        });

        return $this->respond($summary);
    }
}
