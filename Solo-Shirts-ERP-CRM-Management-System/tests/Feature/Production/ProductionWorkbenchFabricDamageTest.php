<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Production\Models\FabricAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->floor = makeUser($this->branch, 'Cutting Master');      // fabric.allocate + damage_reports.create
    $this->supervisor = makeUser($this->branch, 'Production Supervisor');
    $this->fd = makeUser($this->branch, 'Front Desk');             // no fabric/damage permissions
});

/** Reserve fabric on an item through the production-workbench endpoint. */
function reserveOnWorkbench($test, $user, int $itemId, array $body, ?string $key = null)
{
    $key ??= (string) Str::uuid();

    return $test->withHeaders(bearer($user) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/production/items/{$itemId}/fabric-allocation", $body);
}

it('reserves fabric on the workbench and shows it on the item', function () {
    $roll = ledgerRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');

    reserveOnWorkbench($this, $this->floor, $item->id, ['roll_id' => $roll->id, 'metres' => 5])
        ->assertCreated()
        ->assertJsonPath('data.status', 'reserved')
        ->assertJsonPath('data.reserved_metres', '5.00');

    // The workbench (Phase 7A show) now surfaces the allocation + damage summary.
    $show = $this->withHeaders(bearer($this->floor))
        ->getJson("/api/v1/production/items/{$item->id}")
        ->assertOk();

    expect($show->json('data.fabric_status'))->toBe('reserved')
        ->and($show->json('data.fabric_allocation.status'))->toBe('reserved')
        ->and($show->json('data.fabric_allocation.roll.roll_code'))->toBe($roll->roll_code)
        ->and($show->json('data.cloth_damage.count'))->toBe(0);

    // Dedicated allocation read returns the active allocation + history.
    $this->withHeaders(bearer($this->floor))
        ->getJson("/api/v1/production/items/{$item->id}/fabric-allocation")
        ->assertOk()
        ->assertJsonPath('data.active.status', 'reserved')
        ->assertJsonCount(1, 'data.history');
});

it('marks a reservation consumed, deducting stock and releasing the unused tail', function () {
    $roll = ledgerRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');

    reserveOnWorkbench($this, $this->floor, $item->id, ['roll_id' => $roll->id, 'metres' => 5])->assertCreated();

    $this->withHeaders(bearer($this->floor))
        ->patchJson("/api/v1/production/items/{$item->id}/fabric-allocation/consume", ['actual_metres' => 4])
        ->assertOk()
        ->assertJsonPath('data.status', 'consumed')
        ->assertJsonPath('data.consumed_metres', '4.00');

    // 4m left stock (OUT), 1m tail released, so 16m remains and nothing is held.
    expect((float) $roll->fresh()->remaining_metres)->toBe(16.0)
        ->and(FabricMovement::query()->where('type', 'out')->count())->toBe(1)
        ->and(FabricMovement::query()->where('type', 'release')->count())->toBe(1)
        ->and(FabricAllocation::query()->where('status', 'reserved')->count())->toBe(0);
});

it('rejects consuming when the item has no active reservation (409)', function () {
    $item = productionItem($this->branch, 'draft');

    $this->withHeaders(bearer($this->floor))
        ->patchJson("/api/v1/production/items/{$item->id}/fabric-allocation/consume", [])
        ->assertStatus(409)
        ->assertJsonPath('code', 'NO_ACTIVE_RESERVATION');
});

it('reports cloth damage for an item, deriving the roll from its allocation', function () {
    $roll = ledgerRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');
    reserveOnWorkbench($this, $this->floor, $item->id, ['roll_id' => $roll->id, 'metres' => 5])->assertCreated();

    $this->withHeaders(bearer($this->floor))
        ->postJson("/api/v1/production/items/{$item->id}/cloth-damage", [
            'stage' => 'cutting',
            'damage_type' => 'mis_cut',
            'quantity_lost_metres' => 1.5,
            'action_taken' => 'segregated',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    $report = DamageReport::query()->firstOrFail();
    expect($report->fabric_roll_id)->toBe($roll->id)
        ->and($report->order_item_id)->toBe($item->id)
        ->and($report->order_id)->toBe($item->order_id)
        // Reporting never deducts stock — that waits for owner approval.
        ->and((float) $roll->fresh()->remaining_metres)->toBe(20.0)
        ->and(FabricMovement::query()->where('type', 'damage_writeoff')->count())->toBe(0);

    // It shows on the item, on the workbench summary, and on the global feed.
    $this->withHeaders(bearer($this->floor))
        ->getJson("/api/v1/production/items/{$item->id}/cloth-damage")
        ->assertOk()->assertJsonCount(1, 'data');

    $this->withHeaders(bearer($this->floor))
        ->getJson("/api/v1/production/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.cloth_damage.count', 1)
        ->assertJsonPath('data.cloth_damage.total_metres', '1.50');

    $this->withHeaders(bearer($this->supervisor))
        ->getJson('/api/v1/cloth-damage?stage=cutting')
        ->assertOk()->assertJsonCount(1, 'data');
});

it('blocks reporting cloth damage before fabric is allocated (422)', function () {
    $item = productionItem($this->branch, 'draft');

    $this->withHeaders(bearer($this->floor))
        ->postJson("/api/v1/production/items/{$item->id}/cloth-damage", [
            'stage' => 'cutting',
            'damage_type' => 'tear',
            'quantity_lost_metres' => 1,
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');
});

it('requires damage_type_other when the type is other (422)', function () {
    $roll = ledgerRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');
    reserveOnWorkbench($this, $this->floor, $item->id, ['roll_id' => $roll->id, 'metres' => 5])->assertCreated();

    $this->withHeaders(bearer($this->floor))
        ->postJson("/api/v1/production/items/{$item->id}/cloth-damage", [
            'stage' => 'cutting',
            'damage_type' => 'other',
            'quantity_lost_metres' => 1,
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');
});

it('forbids Front Desk from allocating fabric or reporting damage (403)', function () {
    $roll = ledgerRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');

    reserveOnWorkbench($this, $this->fd, $item->id, ['roll_id' => $roll->id, 'metres' => 5])
        ->assertForbidden();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/production/items/{$item->id}/cloth-damage", [
            'stage' => 'cutting',
            'damage_type' => 'tear',
            'quantity_lost_metres' => 1,
        ])
        ->assertForbidden();
});

it('enforces branch scoping on the workbench fabric endpoints (404)', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $otherItem = productionItem($other, 'draft');
    $roll = ledgerRoll($other, 20.0);

    $this->withHeaders(bearer($this->floor))
        ->getJson("/api/v1/production/items/{$otherItem->id}/fabric-allocation")
        ->assertNotFound();

    reserveOnWorkbench($this, $this->floor, $otherItem->id, ['roll_id' => $roll->id, 'metres' => 5])
        ->assertNotFound();
});

it('blocks allocating fabric to an order still in intake preparation', function () {
    $roll = ledgerRoll($this->branch, 20.0);
    $item = productionItem($this->branch, 'draft');
    $item->order->forceFill(['lifecycle_status' => 'intake_preparation'])->save();

    reserveOnWorkbench($this, $this->floor, $item->id, ['roll_id' => $roll->id, 'metres' => 5])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ORDER_NOT_CONFIRMED');
});
