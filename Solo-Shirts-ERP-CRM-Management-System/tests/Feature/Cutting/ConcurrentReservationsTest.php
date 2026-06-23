<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Cutting Master');
});

/**
 * Two reservations chase the last 5 metres on one roll. The row lock serializes
 * them: the first claims all of it, the second re-reads available (now 0) and is
 * rejected. Modeled sequentially since in-process parallelism is unavailable.
 */
it('lets only one of two reservations claim the last metres', function () {
    $roll = fabricRoll($this->branch, 5.0);
    $itemA = productionItem($this->branch, 'draft');
    $itemB = productionItem($this->branch, 'draft');

    allocateFabric($this, $this->user, $itemA->id, ['roll_id' => $roll->id, 'metres' => 5])
        ->assertCreated();

    allocateFabric($this, $this->user, $itemB->id, ['roll_id' => $roll->id, 'metres' => 5])
        ->assertStatus(409)
        ->assertJsonPath('code', 'INSUFFICIENT_AVAILABLE_STOCK');
});
