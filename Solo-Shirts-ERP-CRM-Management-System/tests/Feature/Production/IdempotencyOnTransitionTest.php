<?php

declare(strict_types=1);

use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Production Supervisor');
});

it('requires an Idempotency-Key header', function () {
    $item = productionItem($this->branch, 'cutting');

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/production/items/{$item->id}/transition", ['to' => 'tailoring'])
        ->assertStatus(400)
        ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');
});

it('replays the original response for a repeated key without inserting a second row', function () {
    $item = productionItem($this->branch, 'cutting');
    $key = (string) Str::uuid();

    $first = transitionItem($this, $this->user, $item->id, ['to' => 'tailoring'], $key)
        ->assertOk()
        ->assertJsonPath('data.state', 'tailoring');

    $second = transitionItem($this, $this->user, $item->id, ['to' => 'tailoring'], $key)
        ->assertOk()
        ->assertJsonPath('data.state', 'tailoring');

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and(ProductionTransition::query()->where('order_item_id', $item->id)->count())->toBe(1);
});

it('rejects re-entering the current state under a fresh key (409)', function () {
    $item = productionItem($this->branch, 'cutting');

    transitionItem($this, $this->user, $item->id, ['to' => 'tailoring'])->assertOk();

    // Item is already in tailoring; a brand new key cannot make tailoring → tailoring valid.
    transitionItem($this, $this->user, $item->id, ['to' => 'tailoring'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'INVALID_STATE_TRANSITION');
});
