<?php

declare(strict_types=1);

use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Jobs\ReconcileStockJob;
use App\Modules\Inventory\Models\FabricMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * In-process parallelism is unavailable, so we drive 10 sequential ledger writes
 * through the same lockForUpdate path the concurrent case uses. Each updates the
 * cache atomically, so the final remaining and the ledger sum stay coherent.
 */
it('keeps cache and ledger coherent across 10 movements', function () {
    $branch = makeBranch(['code' => 'HQ']);
    $roll = ledgerRoll($branch, 100.0);
    $ledger = app(StockLedgerInterface::class);

    for ($i = 0; $i < 10; $i++) {
        $ledger->record($roll->id, FabricMovement::TYPE_OUT, 5.0, null, [], null);
    }

    // 100 received − (10 × 5) consumed = 50.
    expect((float) $roll->fresh()->remaining_metres)->toBe(50.0)
        ->and((new ReconcileStockJob)->reconcile())->toBeEmpty();
});
