<?php

declare(strict_types=1);

use App\Modules\Inventory\Contracts\StockLedgerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('available = remaining − net active reserves; reserving never touches remaining', function () {
    $branch = makeBranch(['code' => 'HQ']);
    $roll = ledgerRoll($branch, 20.0);
    $ledger = app(StockLedgerInterface::class);

    $ledger->recordReserve($roll, 999, 5.0, null);
    expect($ledger->availableMetres($roll->fresh()))->toBe(15.0)
        ->and((float) $roll->fresh()->remaining_metres)->toBe(20.0);

    $ledger->recordRelease($roll, 999, 2.0, null);
    expect($ledger->availableMetres($roll->fresh()))->toBe(17.0)
        ->and((float) $roll->fresh()->remaining_metres)->toBe(20.0);

    // Consuming (out) reduces remaining AND closes the remainder of the reserve.
    $ledger->recordConsume($roll, 999, 3.0, null);
    expect((float) $roll->fresh()->remaining_metres)->toBe(17.0)
        ->and($ledger->availableMetres($roll->fresh()))->toBe(17.0);
});
