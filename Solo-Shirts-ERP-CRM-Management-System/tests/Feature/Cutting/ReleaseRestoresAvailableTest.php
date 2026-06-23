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

it('releasing a reservation returns the metres to available', function () {
    $roll = fabricRoll($this->branch, 10.0);
    $item = productionItem($this->branch, 'draft');

    allocateFabric($this, $this->user, $item->id, ['roll_id' => $roll->id, 'metres' => 4])->assertCreated();

    $response = $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/cutting/items/{$item->id}/release-fabric", ['reason' => 'order changed'])
        ->assertOk()
        ->assertJsonPath('data.status', 'released');

    expect($response->json('data.roll.available_metres'))->toBe('10.00');

    $allocation = FabricAllocation::query()->where('order_item_id', $item->id)->sole();
    expect($allocation->status)->toBe('released')
        ->and($allocation->release_reason)->toBe('order changed');
});

it('returns 409 when releasing with no active reservation', function () {
    $item = productionItem($this->branch, 'draft');

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/cutting/items/{$item->id}/release-fabric", ['reason' => 'nothing here'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'NO_ACTIVE_RESERVATION');
});
