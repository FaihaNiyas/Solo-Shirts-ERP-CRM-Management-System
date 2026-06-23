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

/**
 * True OS-level parallelism is not available in-process, so we model the race
 * deterministically: two requests aim the same item at the same target with
 * distinct keys. The first wins and advances the state; the second now
 * re-evaluates against the NEW state — the edge no longer exists, so it is
 * rejected with 409, exactly the lost-race outcome the row lock guarantees.
 */
it('lets one of two competing transitions win and rejects the other (409)', function () {
    $item = productionItem($this->branch, 'cutting');

    transitionItem($this, $this->user, $item->id, ['to' => 'tailoring'])
        ->assertOk()
        ->assertJsonPath('data.state', 'tailoring');

    transitionItem($this, $this->user, $item->id, ['to' => 'tailoring'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'INVALID_STATE_TRANSITION');

    expect((string) $item->fresh()->state)->toBe('tailoring')
        ->and(ProductionTransition::query()->where('order_item_id', $item->id)->count())->toBe(1);
});
