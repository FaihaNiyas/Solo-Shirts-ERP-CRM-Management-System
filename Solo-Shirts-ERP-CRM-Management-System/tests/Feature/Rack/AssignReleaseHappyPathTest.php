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

it('assigns an item to a slot then releases it', function () {
    $slot = rackSlot($this->branch, 'R-A-01');
    $item = productionItem($this->branch, 'ready_for_delivery');

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/rack/items/{$item->id}/assign", ['slot_code' => 'R-A-01'])
        ->assertCreated()
        ->assertJsonPath('data.rack_slot_id', $slot->id);

    expect($slot->fresh()->current_order_item_id)->toBe($item->id)
        ->and($slot->fresh()->occupied_at)->not->toBeNull();

    // current-slot reflects the occupancy.
    $this->withHeaders(bearer($this->staff))
        ->getJson("/api/v1/rack/items/{$item->id}/current-slot")
        ->assertOk()
        ->assertJsonPath('data.slot_code', 'R-A-01');

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/rack/items/{$item->id}/release", ['reason' => 'handed over'])
        ->assertOk();

    expect($slot->fresh()->current_order_item_id)->toBeNull()
        ->and(RackAssignment::query()->where('order_item_id', $item->id)->whereNotNull('released_at')->count())->toBe(1);
});

it('auto-picks the first available slot when no code is given', function () {
    rackSlot($this->branch, 'R-A-02');
    rackSlot($this->branch, 'R-A-01');
    $item = productionItem($this->branch, 'ready_for_delivery');

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/rack/items/{$item->id}/assign", [])
        ->assertCreated();

    // Lowest slot_code wins.
    expect((string) $item->fresh()->id)->not->toBeEmpty();
    $this->withHeaders(bearer($this->staff))
        ->getJson("/api/v1/rack/items/{$item->id}/current-slot")
        ->assertJsonPath('data.slot_code', 'R-A-01');
});

it('returns 409 NO_SLOT_AVAILABLE when the rack is full', function () {
    // Single slot, already occupied.
    rackSlot($this->branch, 'R-A-01');
    $first = productionItem($this->branch, 'ready_for_delivery');
    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/rack/items/{$first->id}/assign", [])->assertCreated();

    $second = productionItem($this->branch, 'ready_for_delivery');
    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/rack/items/{$second->id}/assign", [])
        ->assertStatus(409)
        ->assertJsonPath('code', 'NO_SLOT_AVAILABLE');
});
