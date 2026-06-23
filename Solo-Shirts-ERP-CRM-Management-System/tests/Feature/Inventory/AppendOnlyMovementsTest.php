<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\FabricMovement;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('rejects any UPDATE against fabric_movements at the database level', function () {
    $branch = makeBranch(['code' => 'HQ']);
    ledgerRoll($branch, 20.0);

    $movement = FabricMovement::query()->where('type', 'receive')->sole();

    expect(fn () => DB::table('fabric_movements')
        ->where('id', $movement->id)
        ->update(['reason' => 'tampered']))
        ->toThrow(QueryException::class);

    expect($movement->fresh()->reason)->toBe('seed');
});
