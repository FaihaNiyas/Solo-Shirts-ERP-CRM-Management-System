<?php

declare(strict_types=1);

use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Models\FabricMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->inv = makeUser($this->branch, 'Inventory Manager'); // inventory.view + fabric_rolls.adjust
    $this->fd = makeUser($this->branch, 'Front Desk');         // no inventory permission
});

/** A 100m roll with 30 reserved, 10 consumed (out) and 5 written off. */
function stockedRoll($ctx)
{
    $ledger = app(StockLedgerInterface::class);
    $roll = ledgerRoll($ctx->branch, 100.0);
    $ledger->recordReserve($roll, 1, 30.0, null);
    $ledger->recordConsume($roll, 1, 10.0, null);
    $ledger->record($roll->id, FabricMovement::TYPE_DAMAGE_WRITEOFF, 5.0, 'torn edge', ['type' => 'manual'], null);

    return $roll->fresh();
}

it('exposes available/reserved/consumed/damaged on the roll detail', function () {
    $roll = stockedRoll($this);

    $this->withHeaders(bearer($this->inv))->getJson("/api/v1/inventory/fabric-rolls/{$roll->id}")
        ->assertOk()
        ->assertJsonPath('data.remaining_metres', '85.00')  // 100 − 10 out − 5 writeoff
        ->assertJsonPath('data.available_metres', '65.00')  // 85 − 20 active reserve
        ->assertJsonPath('data.reserved_metres', '20.00')   // 30 reserve − 10 out
        ->assertJsonPath('data.consumed_metres', '10.00')
        ->assertJsonPath('data.damaged_metres', '5.00')
        ->assertJsonPath('data.low_stock', false);
});

it('shows the breakdown on the roll list too', function () {
    stockedRoll($this);

    $this->withHeaders(bearer($this->inv))->getJson('/api/v1/inventory/fabric-rolls')
        ->assertOk()
        ->assertJsonPath('data.0.reserved_metres', '20.00')
        ->assertJsonPath('data.0.consumed_metres', '10.00')
        ->assertJsonPath('data.0.damaged_metres', '5.00');
});

it('returns the per-roll ledger with movements and breakdown', function () {
    $roll = stockedRoll($this);

    $res = $this->withHeaders(bearer($this->inv))->getJson("/api/v1/inventory/fabric-rolls/{$roll->id}/ledger")
        ->assertOk()
        ->assertJsonPath('data.breakdown.consumed_metres', '10.00')
        ->assertJsonPath('data.breakdown.damaged_metres', '5.00');

    // receive + reserve + out + damage_writeoff = 4 movements.
    expect($res->json('data.movements'))->toHaveCount(4)
        ->and(collect($res->json('data.movements'))->pluck('type')->all())
        ->toContain('receive', 'reserve', 'out', 'damage_writeoff');
});

it('sets a per-roll low-stock threshold and flips the low_stock flag', function () {
    $roll = stockedRoll($this); // remaining 85

    // Threshold above remaining → low stock.
    $this->withHeaders(bearer($this->inv))->patchJson("/api/v1/inventory/fabric-rolls/{$roll->id}/threshold", ['low_stock_threshold_metres' => 90])
        ->assertOk()
        ->assertJsonPath('data.low_stock_threshold_metres', '90.00')
        ->assertJsonPath('data.low_stock', true);

    // Threshold below remaining → healthy.
    $this->withHeaders(bearer($this->inv))->patchJson("/api/v1/inventory/fabric-rolls/{$roll->id}/threshold", ['low_stock_threshold_metres' => 50])
        ->assertOk()
        ->assertJsonPath('data.low_stock', false);
});

it('clears the threshold when set to null', function () {
    $roll = stockedRoll($this);
    $this->withHeaders(bearer($this->inv))->patchJson("/api/v1/inventory/fabric-rolls/{$roll->id}/threshold", ['low_stock_threshold_metres' => 90])->assertOk();

    $this->withHeaders(bearer($this->inv))->patchJson("/api/v1/inventory/fabric-rolls/{$roll->id}/threshold", ['low_stock_threshold_metres' => null])
        ->assertOk()
        ->assertJsonPath('data.low_stock_threshold_metres', null)
        ->assertJsonPath('data.low_stock', false);
});

it('forbids Front Desk from inventory stock views and threshold edits (403)', function () {
    $roll = stockedRoll($this);

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/inventory/fabric-rolls')->assertForbidden();
    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/inventory/fabric-rolls/{$roll->id}/ledger")->assertForbidden();
    $this->withHeaders(bearer($this->fd))->patchJson("/api/v1/inventory/fabric-rolls/{$roll->id}/threshold", ['low_stock_threshold_metres' => 10])->assertForbidden();
});

it('enforces branch scoping on the stock endpoints (404)', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $foreign = ledgerRoll($other, 50.0);

    $this->withHeaders(bearer($this->inv))->getJson("/api/v1/inventory/fabric-rolls/{$foreign->id}/ledger")->assertNotFound();
    $this->withHeaders(bearer($this->inv))->patchJson("/api/v1/inventory/fabric-rolls/{$foreign->id}/threshold", ['low_stock_threshold_metres' => 10])->assertNotFound();
});
