<?php

declare(strict_types=1);

use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\TailorAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->supervisor = makeUser($this->branch, 'Production Supervisor');
    $this->tailor = makeUser($this->branch, 'Tailor');
});

it('assigns, starts and completes a bundle, advancing the item to kaja_button', function () {
    $bundle = cutBundleFor($this->branch, 'tailoring');

    $assignment = $this->withHeaders(bearer($this->supervisor))
        ->postJson('/api/v1/tailoring/assignments', [
            'bundle_id' => $bundle->id,
            'tailor_id' => $this->tailor->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'assigned')
        ->json('data.id');

    $this->withHeaders(bearer($this->tailor))
        ->postJson("/api/v1/tailoring/assignments/{$assignment}/start")
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress');

    $this->withHeaders(bearer($this->tailor))
        ->postJson("/api/v1/tailoring/assignments/{$assignment}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $row = TailorAssignment::query()->find($assignment);
    expect($row->started_at)->not->toBeNull()
        ->and($row->completed_at)->not->toBeNull();

    expect((string) $bundle->orderItem->fresh()->state)->toBe(OrderItem::STATE_KAJA_BUTTON);
});

it('forbids a tailor starting another tailor\'s assignment', function () {
    $bundle = cutBundleFor($this->branch, 'tailoring');
    $otherTailor = makeUser($this->branch, 'Tailor');

    $assignment = TailorAssignment::factory()->create([
        'bundle_id' => $bundle->id,
        'order_item_id' => $bundle->order_item_id,
        'branch_id' => $this->branch->id,
        'tailor_id' => $this->tailor->id,
    ]);

    $this->withHeaders(bearer($otherTailor))
        ->postJson("/api/v1/tailoring/assignments/{$assignment->id}/start")
        ->assertForbidden();
});

it('rejects completing an assignment whose item is cancelled (409)', function () {
    $bundle = cutBundleFor($this->branch, 'cancelled');

    $assignment = TailorAssignment::factory()->started()->create([
        'bundle_id' => $bundle->id,
        'order_item_id' => $bundle->order_item_id,
        'branch_id' => $this->branch->id,
        'tailor_id' => $this->tailor->id,
    ]);

    $this->withHeaders(bearer($this->tailor))
        ->postJson("/api/v1/tailoring/assignments/{$assignment->id}/complete")
        ->assertStatus(409)
        ->assertJsonPath('code', 'ITEM_CANCELLED');
});
