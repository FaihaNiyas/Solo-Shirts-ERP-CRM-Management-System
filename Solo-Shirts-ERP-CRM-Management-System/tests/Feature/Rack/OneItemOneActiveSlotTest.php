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

it('rejects assigning a second slot to an already-assigned item (409)', function () {
    rackSlot($this->branch, 'R-A-01');
    rackSlot($this->branch, 'R-A-02');
    $item = productionItem($this->branch, 'ready_for_delivery');

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/rack/items/{$item->id}/assign", ['slot_code' => 'R-A-01'])
        ->assertCreated();

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/rack/items/{$item->id}/assign", ['slot_code' => 'R-A-02'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ITEM_ALREADY_ASSIGNED');
});

it('enforces one active assignment per item at the database level', function () {
    $branch = $this->branch;
    $slot1 = rackSlot($branch, 'R-A-01');
    $slot2 = rackSlot($branch, 'R-A-02');
    $item = productionItem($branch, 'ready_for_delivery');

    RackAssignment::factory()->for($branch)->create([
        'rack_slot_id' => $slot1->id,
        'order_item_id' => $item->id,
    ]);

    expect(fn () => RackAssignment::factory()->for($branch)->create([
        'rack_slot_id' => $slot2->id,
        'order_item_id' => $item->id,
    ]))->toThrow(QueryException::class);
});
