<?php

declare(strict_types=1);

use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Production Supervisor');
});

it('rejects a skip-ahead transition (draft → delivered) with 409', function () {
    $item = productionItem($this->branch, 'draft');

    transitionItem($this, $this->user, $item->id, ['to' => 'delivered'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'INVALID_STATE_TRANSITION');

    expect((string) $item->fresh()->state)->toBe('draft')
        ->and(ProductionTransition::query()->count())->toBe(0);
});

it('rejects a backwards transition (tailoring → cutting) with 409', function () {
    // The actor is authorized for the 'cutting' leg, so this exercises the state
    // machine itself — the edge tailoring → cutting simply does not exist.
    $item = productionItem($this->branch, 'tailoring');

    transitionItem($this, $this->user, $item->id, ['to' => 'cutting'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'INVALID_STATE_TRANSITION');
});

it('treats delivered as terminal', function () {
    $item = productionItem($this->branch, 'delivered');

    transitionItem($this, $this->user, $item->id, ['to' => 'cancelled', 'notes' => 'too late'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'INVALID_STATE_TRANSITION');
});

it('treats cancelled as terminal', function () {
    $item = productionItem($this->branch, 'cancelled');

    transitionItem($this, $this->user, $item->id, ['to' => 'cutting'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'INVALID_STATE_TRANSITION');
});

it('rejects an unknown target state at validation (422)', function () {
    $item = productionItem($this->branch, 'draft');

    transitionItem($this, $this->user, $item->id, ['to' => 'teleported'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');
});
