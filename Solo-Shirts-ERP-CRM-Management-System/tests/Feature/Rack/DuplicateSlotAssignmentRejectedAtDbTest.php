<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\RackAssignment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('rejects a second assignment to an occupied slot (409 RACK_SLOT_OCCUPIED)', function () {
    rackSlot($this->branch, 'R-A-01');
    $itemA = productionItem($this->branch, 'ready_for_delivery');
    $itemB = productionItem($this->branch, 'ready_for_delivery');

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/rack/items/{$itemA->id}/assign", ['slot_code' => 'R-A-01'])
        ->assertCreated();

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/rack/items/{$itemB->id}/assign", ['slot_code' => 'R-A-01'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'RACK_SLOT_OCCUPIED');
});

it('enforces one active assignment per slot at the database level', function () {
    $branch = $this->branch;
    $slot = rackSlot($branch, 'R-A-01');
    $itemA = productionItem($branch, 'ready_for_delivery');
    $itemB = productionItem($branch, 'ready_for_delivery');

    RackAssignment::factory()->for($branch)->create([
        'rack_slot_id' => $slot->id,
        'order_item_id' => $itemA->id,
    ]);

    // A second ACTIVE row for the same slot violates the partial-unique index.
    expect(fn () => RackAssignment::factory()->for($branch)->create([
        'rack_slot_id' => $slot->id,
        'order_item_id' => $itemB->id,
    ]))->toThrow(QueryException::class);
});
