<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Jobs;

use App\Modules\Inventory\Services\LowStockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Daily morning scan: logs a low-stock alert for every (branch, fabric_type)
 * below its configured threshold. Phase 17 schedules this and wires real
 * notification channels.
 */
final class LowStockAlertJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(LowStockService $lowStock): int
    {
        $rows = $lowStock->lowStock();

        foreach ($rows as $row) {
            activity('inventory')
                ->event('low-stock')
                ->withProperties([
                    'branch_id' => (int) $row->branch_id,
                    'fabric_type_id' => (int) $row->fabric_type_id,
                    'code' => $row->code,
                    'total_remaining' => (float) $row->total_remaining,
                    'threshold' => (float) $row->threshold,
                ])
                ->log("low stock: {$row->code} at {$row->total_remaining}m (threshold {$row->threshold}m)");
        }

        return $rows->count();
    }
}
