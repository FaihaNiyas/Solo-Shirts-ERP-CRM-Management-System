<?php

declare(strict_types=1);

use App\Modules\Inventory\Jobs\ReconcileStockJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('flags a roll whose cached remaining drifts from the ledger', function () {
    $branch = makeBranch(['code' => 'HQ']);
    $roll = ledgerRoll($branch, 20.0);

    // Corrupt the cache behind the ledger's back.
    DB::table('fabric_rolls')->where('id', $roll->id)->update(['remaining_metres' => 13]);

    $drifts = (new ReconcileStockJob)->reconcile();

    expect($drifts)->toHaveCount(1)
        ->and($drifts[0]['roll_id'])->toBe($roll->id)
        ->and($drifts[0]['ledger'])->toBe(20.0)
        ->and($drifts[0]['cached'])->toBe(13.0);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'inventory',
        'event' => 'stock-drift',
        'subject_id' => $roll->id,
    ]);
});

it('reports no drift when cache and ledger agree', function () {
    $branch = makeBranch(['code' => 'HQ']);
    ledgerRoll($branch, 20.0);

    expect((new ReconcileStockJob)->reconcile())->toBeEmpty();
});
