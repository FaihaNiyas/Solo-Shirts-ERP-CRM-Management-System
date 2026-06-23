<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Production\Models\FabricAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Cutting Master');
});

it('replays an allocation for a repeated key without a duplicate reservation or movement', function () {
    $roll = fabricRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');
    $key = (string) Str::uuid();

    $first = allocateFabric($this, $this->user, $item->id, ['roll_id' => $roll->id, 'metres' => 3], $key)
        ->assertCreated();

    $second = allocateFabric($this, $this->user, $item->id, ['roll_id' => $roll->id, 'metres' => 3], $key)
        ->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and(FabricAllocation::query()->count())->toBe(1)
        ->and(FabricMovement::query()->where('type', 'reserve')->count())->toBe(1);
});

it('requires an Idempotency-Key for allocation', function () {
    $roll = fabricRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/cutting/items/{$item->id}/allocate-fabric", ['roll_id' => $roll->id, 'metres' => 3])
        ->assertStatus(400)
        ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');
});
