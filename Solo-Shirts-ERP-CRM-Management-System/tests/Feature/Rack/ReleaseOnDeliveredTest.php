<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\RackAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('releases the rack slot when the item is delivered', function () {
    $slot = rackSlot($this->branch, 'R-A-01');
    $item = productionItem($this->branch, 'ready_for_delivery');

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/rack/items/{$item->id}/assign", ['slot_code' => 'R-A-01'])
        ->assertCreated();

    expect($slot->fresh()->current_order_item_id)->toBe($item->id);

    // Delivering the item fires the release listener.
    transitionItem($this, $this->staff, $item->id, ['to' => 'delivered'])
        ->assertOk()
        ->assertJsonPath('data.state', 'delivered');

    expect($slot->fresh()->current_order_item_id)->toBeNull()
        ->and(RackAssignment::query()->where('order_item_id', $item->id)->sole()->released_at)->not->toBeNull();
});
