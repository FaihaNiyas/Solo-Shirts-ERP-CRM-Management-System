<?php

declare(strict_types=1);

use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Exceptions\InventoryException;
use App\Modules\Inventory\Models\FabricMovement;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('forbids remaining_metres going negative at the database level', function () {
    $branch = makeBranch(['code' => 'HQ']);
    $roll = ledgerRoll($branch, 10.0);

    expect(fn () => DB::table('fabric_rolls')
        ->where('id', $roll->id)
        ->update(['remaining_metres' => -1]))
        ->toThrow(QueryException::class);
});

it('the ledger refuses to deduct more than remaining (INSUFFICIENT_STOCK)', function () {
    $branch = makeBranch(['code' => 'HQ']);
    $roll = ledgerRoll($branch, 4.0);

    expect(fn () => app(StockLedgerInterface::class)->record(
        $roll->id,
        FabricMovement::TYPE_OUT,
        10.0,
        null,
        [],
        null,
    ))->toThrow(InventoryException::class);

    expect((float) $roll->fresh()->remaining_metres)->toBe(4.0);
});
