<?php

declare(strict_types=1);

use App\Modules\Production\Models\FabricAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Cutting Master');
});

it('cannot reserve fabric from another branch roll', function () {
    $otherBranch = makeBranch(['code' => 'BR2']);
    $foreignRoll = fabricRoll($otherBranch, 20.0);
    $item = productionItem($this->branch, 'draft');

    allocateFabric($this, $this->user, $item->id, ['roll_id' => $foreignRoll->id, 'metres' => 3])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_ROLL');

    expect(FabricAllocation::query()->count())->toBe(0);
});

it('cannot allocate against a cross-branch order item (404)', function () {
    $otherBranch = makeBranch(['code' => 'BR2']);
    $roll = fabricRoll($this->branch, 20.0);
    $foreignItem = productionItem($otherBranch, 'draft');

    allocateFabric($this, $this->user, $foreignItem->id, ['roll_id' => $roll->id, 'metres' => 3])
        ->assertNotFound();
});
