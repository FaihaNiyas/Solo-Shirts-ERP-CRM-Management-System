<?php

declare(strict_types=1);

use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Production\Models\QcInspection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->inspector = makeUser($this->branch, 'QC Supervisor');
});

it('a passing inspection transitions the item to packing', function () {
    $item = productionItem($this->branch, 'qc');

    $this->withHeaders(bearer($this->inspector))
        ->postJson("/api/v1/qc/items/{$item->id}/inspect", ['disposition' => 'pass'])
        ->assertCreated()
        ->assertJsonPath('data.disposition', 'pass')
        ->assertJsonPath('data.attempt_number', 1);

    expect((string) $item->fresh()->state)->toBe(OrderItem::STATE_PACKING)
        ->and(QcInspection::query()->where('order_item_id', $item->id)->count())->toBe(1);
});

it('a reject routes the item to cancelled with a refund flag and requires a reason', function () {
    $item = productionItem($this->branch, 'qc');

    // Missing reason → 422.
    $this->withHeaders(bearer($this->inspector))
        ->postJson("/api/v1/qc/items/{$item->id}/inspect", ['disposition' => 'reject'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');

    $this->withHeaders(bearer($this->inspector))
        ->postJson("/api/v1/qc/items/{$item->id}/inspect", [
            'disposition' => 'reject',
            'notes' => 'irreparable fabric tear',
        ])
        ->assertCreated();

    expect((string) $item->fresh()->state)->toBe(OrderItem::STATE_CANCELLED);

    $transition = ProductionTransition::query()
        ->where('order_item_id', $item->id)
        ->where('to_state', 'cancelled')
        ->sole();
    expect($transition->metadata)->toMatchArray(['refund' => true]);
});

it('rejects inspecting an item that is not in QC (409)', function () {
    $item = productionItem($this->branch, 'tailoring');

    $this->withHeaders(bearer($this->inspector))
        ->postJson("/api/v1/qc/items/{$item->id}/inspect", ['disposition' => 'pass'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'NOT_IN_QC');
});
