<?php

declare(strict_types=1);

use App\Modules\Production\Models\CutBundle;
use App\Modules\Production\Models\FabricAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Cutting Master');
});

it('consumes fabric, creates bundles and advances the item to tailoring', function () {
    $roll = fabricRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');

    allocateFabric($this, $this->user, $item->id, ['roll_id' => $roll->id, 'metres' => 5])->assertCreated();

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/cutting/items/{$item->id}/start-cutting")
        ->assertOk()
        ->assertJsonPath('data.state', 'cutting');

    $response = $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/cutting/items/{$item->id}/complete-cutting", [
            'actual_metres' => 4,
            'bundles' => [['pieces' => 3], ['pieces' => 2]],
        ])
        ->assertOk();

    expect($response->json('data.item.state'))->toBe('tailoring')
        ->and($response->json('data.bundles'))->toHaveCount(2)
        ->and($response->json('data.bundles.0.bundle_code'))->not->toBeEmpty();

    // 20 − 4 actually consumed; the 1m unused tail of the 5m reserve was released.
    expect($roll->fresh()->remaining_metres)->toBe('16.00');

    $allocation = FabricAllocation::query()->sole();
    expect($allocation->status)->toBe('consumed')
        ->and($allocation->consumed_metres)->toBe('4.00');

    expect(CutBundle::query()->count())->toBe(2)
        ->and(CutBundle::query()->pluck('bundle_code')->all())
        ->toBe([$item->item_code . '-B01', $item->item_code . '-B02']);
});

it('rejects completing cutting when the item has no reservation', function () {
    $item = productionItem($this->branch, 'cutting');

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/cutting/items/{$item->id}/complete-cutting", [
            'actual_metres' => 4,
            'bundles' => [['pieces' => 1]],
        ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'NO_ACTIVE_RESERVATION');
});

it('requires at least one bundle', function () {
    $roll = fabricRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');
    allocateFabric($this, $this->user, $item->id, ['roll_id' => $roll->id, 'metres' => 5])->assertCreated();
    $this->withHeaders(bearer($this->user))->postJson("/api/v1/cutting/items/{$item->id}/start-cutting")->assertOk();

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/cutting/items/{$item->id}/complete-cutting", [
            'actual_metres' => 4,
            'bundles' => [],
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');
});
