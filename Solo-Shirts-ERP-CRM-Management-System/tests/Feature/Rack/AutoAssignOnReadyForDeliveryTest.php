<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Delivery\Services\RackSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('auto-assigns the first available slot when an item becomes ready for delivery', function () {
    rackSlot($this->branch, 'R-A-02');
    rackSlot($this->branch, 'R-A-01');
    $item = productionItem($this->branch, 'packing');

    transitionItem($this, $this->staff, $item->id, ['to' => 'ready_for_delivery'])
        ->assertOk()
        ->assertJsonPath('data.state', 'ready_for_delivery');

    $assignment = RackAssignment::query()->where('order_item_id', $item->id)->whereNull('released_at')->sole();
    $slot = RackSlot::query()->where('slot_code', 'R-A-01')->sole();

    expect($assignment->rack_slot_id)->toBe($slot->id)
        ->and($slot->fresh()->current_order_item_id)->toBe($item->id);
});

it('does not fail the transition when the rack is full', function () {
    // One slot, pre-occupied by another item.
    $slot = rackSlot($this->branch, 'R-A-01');
    $occupier = productionItem($this->branch, 'ready_for_delivery');
    app(RackSlotService::class)->assign($occupier->id, 'R-A-01', null);

    $item = productionItem($this->branch, 'packing');

    // Transition still succeeds even though no slot is free.
    transitionItem($this, $this->staff, $item->id, ['to' => 'ready_for_delivery'])
        ->assertOk()
        ->assertJsonPath('data.state', 'ready_for_delivery');

    expect(RackAssignment::query()->where('order_item_id', $item->id)->exists())->toBeFalse();
});
