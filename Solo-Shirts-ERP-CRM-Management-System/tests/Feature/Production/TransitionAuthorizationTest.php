<?php

declare(strict_types=1);

use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('lets a Cutting Master begin cutting', function () {
    $cuttingMaster = makeUser($this->branch, 'Cutting Master');
    $item = productionItem($this->branch, 'fabric_allocated');

    transitionItem($this, $cuttingMaster, $item->id, ['to' => 'cutting'])
        ->assertOk()
        ->assertJsonPath('data.state', 'cutting');
});

it('lets a Front Desk user move a card on the board (single-operator mode)', function () {
    $frontDesk = makeUser($this->branch, 'Front Desk');
    $item = productionItem($this->branch, 'fabric_allocated');

    transitionItem($this, $frontDesk, $item->id, ['to' => 'cutting'])
        ->assertOk()
        ->assertJsonPath('data.state', 'cutting');
});

it('forbids a role from a transition it does not own (403)', function () {
    // A Cutting Master has no authority to sign off QC packing.
    $cuttingMaster = makeUser($this->branch, 'Cutting Master');
    $item = productionItem($this->branch, 'qc');

    transitionItem($this, $cuttingMaster, $item->id, ['to' => 'packing'])
        ->assertForbidden();

    expect((string) $item->fresh()->state)->toBe('qc')
        ->and(ProductionTransition::query()->count())->toBe(0);
});

it('forbids cross-branch transitions (item 404s under branch scope)', function () {
    $other = makeBranch(['code' => 'BR2']);
    $supervisor = makeUser($this->branch, 'Production Supervisor');
    $item = productionItem($other, 'cutting');

    transitionItem($this, $supervisor, $item->id, ['to' => 'tailoring'])
        ->assertNotFound();
});
