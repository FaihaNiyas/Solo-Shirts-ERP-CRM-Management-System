<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Jobs;

use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Nightly reconciliation: for every roll, recompute remaining from the ledger
 * and compare to the cached value. Each mismatch is logged for the Owner.
 * Phase 17 schedules this.
 */
final class ReconcileStockJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const TOLERANCE = 0.001;

    /**
     * @return list<array{roll_id: int, cached: float, ledger: float}>
     */
    public function reconcile(): array
    {
        $drifts = [];

        FabricRoll::query()->withoutGlobalScopes()->chunkById(200, function ($rolls) use (&$drifts): void {
            foreach ($rolls as $roll) {
                $ledger = $this->ledgerRemaining($roll->id);
                $cached = (float) $roll->remaining_metres;

                if (abs($ledger - $cached) > self::TOLERANCE) {
                    $drifts[] = ['roll_id' => $roll->id, 'cached' => $cached, 'ledger' => $ledger];

                    activity('inventory')
                        ->performedOn($roll)
                        ->event('stock-drift')
                        ->withProperties(['cached' => $cached, 'ledger' => $ledger])
                        ->log("stock drift on roll {$roll->id}: cached {$cached} vs ledger {$ledger}");
                }
            }
        });

        return $drifts;
    }

    public function handle(): void
    {
        $this->reconcile();
    }

    private function ledgerRemaining(int $rollId): float
    {
        $additions = (float) FabricMovement::query()
            ->where('fabric_roll_id', $rollId)
            ->whereIn('type', FabricMovement::ADDITIONS)
            ->sum('metres');

        $deductions = (float) FabricMovement::query()
            ->where('fabric_roll_id', $rollId)
            ->whereIn('type', FabricMovement::DEDUCTIONS)
            ->sum('metres');

        return $additions - $deductions;
    }
}
