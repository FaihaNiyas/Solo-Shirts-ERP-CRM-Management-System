<?php

declare(strict_types=1);

use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Production Supervisor');
});

it('rejects any UPDATE against production_transitions at the database level', function () {
    $item = productionItem($this->branch, 'cutting');
    transitionItem($this, $this->user, $item->id, ['to' => 'tailoring'])->assertOk();

    $row = ProductionTransition::query()->sole();

    expect(fn () => DB::table('production_transitions')
        ->where('id', $row->id)
        ->update(['notes' => 'tampered']))
        ->toThrow(QueryException::class);

    // The original row is untouched.
    expect($row->fresh()->notes)->toBeNull();
});
