<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Production\Models\PackingChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->packer = makeUser($this->branch, 'Production Supervisor'); // production.packing.manage
    $this->fd = makeUser($this->branch, 'Front Desk');               // no packing permission
});

/** Every checklist box ticked. */
function fullChecklist(): array
{
    return array_fill_keys(PackingChecklist::REQUIRED_CHECKS, true);
}

function saveChecklist($test, $user, int $itemId, array $body)
{
    return $test->withHeaders(bearer($user))->postJson("/api/v1/production/items/{$itemId}/packing-checklist", $body);
}

function markPacked($test, $user, int $itemId)
{
    return $test->withHeaders(bearer($user))->postJson("/api/v1/production/items/{$itemId}/mark-packed");
}

it('cannot mark packed before QC pass (item not in packing → 409)', function () {
    $item = productionItem($this->branch, 'qc');

    markPacked($this, $this->packer, $item->id)
        ->assertStatus(409)
        ->assertJsonPath('code', 'NOT_IN_PACKING');
});

it('saves the packing checklist once the item is in packing', function () {
    $item = productionItem($this->branch, 'packing');

    saveChecklist($this, $this->packer, $item->id, ['checked_buttons' => true, 'checked_ironing' => true, 'notes' => 'pressed'])
        ->assertOk()
        ->assertJsonPath('data.checklist.checked_buttons', true)
        ->assertJsonPath('data.checklist.checked_ironing', true)
        ->assertJsonPath('data.checklist_complete', false);

    expect(PackingChecklist::query()->where('order_item_id', $item->id)->exists())->toBeTrue();
});

it('cannot mark packed while the checklist is incomplete (422)', function () {
    $item = productionItem($this->branch, 'packing');
    saveChecklist($this, $this->packer, $item->id, ['checked_buttons' => true])->assertOk();

    markPacked($this, $this->packer, $item->id)
        ->assertStatus(422)
        ->assertJsonPath('code', 'PACKING_CHECKLIST_INCOMPLETE');

    expect((string) $item->fresh()->state)->toBe('packing');
});

it('marks packed once the checklist is complete and moves the item to ready', function () {
    rackSlot($this->branch, 'R-A-01');
    $item = productionItem($this->branch, 'packing');
    saveChecklist($this, $this->packer, $item->id, fullChecklist())->assertOk();

    markPacked($this, $this->packer, $item->id)
        ->assertOk()
        ->assertJsonPath('data.is_ready', true)
        ->assertJsonPath('data.is_delivered', false)
        ->assertJsonPath('data.rack_slot.slot_code', 'R-A-01');

    $checklist = PackingChecklist::query()->where('order_item_id', $item->id)->sole();
    expect((string) $item->fresh()->state)->toBe('ready_for_delivery')
        ->and($checklist->packed_by)->toBe($this->packer->id)
        ->and($checklist->packed_at)->not->toBeNull();
});

it('auto-assigns a ready-rack slot after packing', function () {
    rackSlot($this->branch, 'R-A-01');
    $item = productionItem($this->branch, 'packing');
    saveChecklist($this, $this->packer, $item->id, fullChecklist())->assertOk();
    markPacked($this, $this->packer, $item->id)->assertOk();

    expect(RackAssignment::query()->where('order_item_id', $item->id)->whereNull('released_at')->exists())->toBeTrue();
});

it('shows the packed item in the ready-rack search', function () {
    rackSlot($this->branch, 'R-A-01');
    $item = productionItem($this->branch, 'packing');
    $orderCode = $item->order->order_code;
    saveChecklist($this, $this->packer, $item->id, fullChecklist())->assertOk();
    markPacked($this, $this->packer, $item->id)->assertOk();

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/rack/search?q=' . $orderCode)
        ->assertOk()
        ->assertJsonPath('data.results.0.ready', true)
        ->assertJsonPath('data.results.0.ready_sub_orders.0.item_code', $item->item_code);
});

it('generates a per-item packing slip PDF', function () {
    $item = productionItem($this->branch, 'packing');
    saveChecklist($this, $this->packer, $item->id, fullChecklist())->assertOk();

    $this->withHeaders(bearer($this->packer))->getJson("/api/v1/production/items/{$item->id}/packing-slip")
        ->assertCreated()
        ->assertJsonPath('data.order_item_id', $item->id)
        ->assertJsonPath('data.item_code', $item->item_code);
});

it('packing never marks the item delivered', function () {
    rackSlot($this->branch, 'R-A-01');
    $item = productionItem($this->branch, 'packing');
    saveChecklist($this, $this->packer, $item->id, fullChecklist())->assertOk();
    markPacked($this, $this->packer, $item->id)->assertOk();

    expect((string) $item->fresh()->state)->not->toBe('delivered');
});

it('releases the rack slot when the item is later delivered', function () {
    rackSlot($this->branch, 'R-A-01');
    $item = productionItem($this->branch, 'packing');
    saveChecklist($this, $this->packer, $item->id, fullChecklist())->assertOk();
    markPacked($this, $this->packer, $item->id)->assertOk();

    // Delivery releases the slot via the existing OnDeliveredOrCancelledReleaseSlot listener.
    transitionItem($this, $this->packer, $item->id, ['to' => 'delivered'])->assertOk();

    expect(RackAssignment::query()->where('order_item_id', $item->id)->whereNull('released_at')->exists())->toBeFalse();
});

it('forbids Front Desk from saving the checklist or marking packed (403)', function () {
    $item = productionItem($this->branch, 'packing');

    saveChecklist($this, $this->fd, $item->id, fullChecklist())->assertForbidden();
    markPacked($this, $this->fd, $item->id)->assertForbidden();

    expect(PackingChecklist::query()->count())->toBe(0)
        ->and((string) $item->fresh()->state)->toBe('packing');
});

it('enforces branch scoping on the packing endpoints (404)', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $otherItem = productionItem($other, 'packing');

    saveChecklist($this, $this->packer, $otherItem->id, fullChecklist())->assertNotFound();
    markPacked($this, $this->packer, $otherItem->id)->assertNotFound();
    $this->withHeaders(bearer($this->packer))->getJson("/api/v1/production/items/{$otherItem->id}/packing")->assertNotFound();
});

it('creates no invoice or payment side effects when packing', function () {
    rackSlot($this->branch, 'R-A-01');
    $item = productionItem($this->branch, 'packing');
    saveChecklist($this, $this->packer, $item->id, fullChecklist())->assertOk();
    markPacked($this, $this->packer, $item->id)->assertOk();

    expect(DB::table('invoices')->count())->toBe(0)
        ->and(DB::table('payments')->count())->toBe(0);
});
