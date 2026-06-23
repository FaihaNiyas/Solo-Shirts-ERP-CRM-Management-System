<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Cutting Master');
});

it('reserving fabric reduces available but not physical remaining', function () {
    $roll = fabricRoll($this->branch, 10.0);
    $item = productionItem($this->branch, 'draft');

    $response = allocateFabric($this, $this->user, $item->id, ['roll_id' => $roll->id, 'metres' => 3])
        ->assertCreated();

    expect($response->json('data.roll.remaining_metres'))->toBe('10.00')
        ->and($response->json('data.roll.available_metres'))->toBe('7.00');

    // Physical stock untouched; only a reserve movement exists.
    expect($roll->fresh()->remaining_metres)->toBe('10.00')
        ->and(FabricMovement::query()->where('type', 'reserve')->count())->toBe(1)
        ->and(FabricMovement::query()->where('type', 'out')->count())->toBe(0);

    // The reservation also advanced the item to FabricAllocated.
    expect((string) $item->fresh()->state)->toBe(OrderItem::STATE_FABRIC_ALLOCATED);
});

it('rejects reserving more than the available stock', function () {
    $roll = fabricRoll($this->branch, 4.0);
    $item = productionItem($this->branch, 'draft');

    allocateFabric($this, $this->user, $item->id, ['roll_id' => $roll->id, 'metres' => 5])
        ->assertStatus(409)
        ->assertJsonPath('code', 'INSUFFICIENT_AVAILABLE_STOCK');
});

it('rejects a second allocation for an already-reserved item', function () {
    $roll = fabricRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');

    allocateFabric($this, $this->user, $item->id, ['roll_id' => $roll->id, 'metres' => 3])->assertCreated();

    allocateFabric($this, $this->user, $item->id, ['roll_id' => $roll->id, 'metres' => 3])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ALREADY_ALLOCATED');
});

it('rejects reserving against a written-off roll', function () {
    $roll = fabricRoll($this->branch, 20.0);
    $roll->update(['status' => 'written_off']);
    $item = productionItem($this->branch, 'draft');

    allocateFabric($this, $this->user, $item->id, ['roll_id' => $roll->id, 'metres' => 3])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ROLL_NOT_AVAILABLE');
});
