<?php

declare(strict_types=1);

use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    // Production Supervisor owns every leg of the workflow.
    $this->user = makeUser($this->branch, 'Production Supervisor');
});

dataset('valid_edges', [
    'draft → fabric_allocated' => ['draft', 'fabric_allocated'],
    'fabric_allocated → cutting' => ['fabric_allocated', 'cutting'],
    'cutting → tailoring' => ['cutting', 'tailoring'],
    'tailoring → kaja_button' => ['tailoring', 'kaja_button'],
    'kaja_button → finishing' => ['kaja_button', 'finishing'],
    'finishing → qc' => ['finishing', 'qc'],
    'qc → packing' => ['qc', 'packing'],
    'packing → ready_for_delivery' => ['packing', 'ready_for_delivery'],
    'ready_for_delivery → delivered' => ['ready_for_delivery', 'delivered'],
    'qc → rework' => ['qc', 'rework'],
    'rework → qc' => ['rework', 'qc'],
    'draft → cancelled' => ['draft', 'cancelled'],
    'fabric_allocated → cancelled' => ['fabric_allocated', 'cancelled'],
    'cutting → cancelled' => ['cutting', 'cancelled'],
]);

it('allows every defined edge of the production state machine', function (string $from, string $to) {
    $item = productionItem($this->branch, $from);

    transitionItem($this, $this->user, $item->id, ['to' => $to, 'notes' => 'moving along'])
        ->assertOk()
        ->assertJsonPath('data.state', $to);

    expect((string) $item->fresh()->state)->toBe($to)
        ->and(ProductionTransition::query()
            ->where('order_item_id', $item->id)
            ->where('from_state', $from)
            ->where('to_state', $to)
            ->exists())->toBeTrue();
})->with('valid_edges');

it('records the actor, from, to and occurred_at on each transition', function () {
    $item = productionItem($this->branch, 'cutting');

    transitionItem($this, $this->user, $item->id, ['to' => 'tailoring'])->assertOk();

    $row = ProductionTransition::query()->where('order_item_id', $item->id)->sole();

    expect($row->from_state)->toBe('cutting')
        ->and($row->to_state)->toBe('tailoring')
        ->and($row->actor_id)->toBe($this->user->id)
        ->and($row->occurred_at)->not->toBeNull();
});

it('marks cancelled_at and reason when an item is cancelled', function () {
    $item = productionItem($this->branch, 'draft');

    transitionItem($this, $this->user, $item->id, ['to' => 'cancelled', 'notes' => 'customer withdrew'])
        ->assertOk();

    $fresh = $item->fresh();
    expect((string) $fresh->state)->toBe('cancelled')
        ->and($fresh->cancelled_at)->not->toBeNull()
        ->and($fresh->cancel_reason)->toBe('customer withdrew');
});

it('exposes the allowed next states on the item resource', function () {
    $item = productionItem($this->branch, 'qc');

    $response = transitionItem($this, $this->user, $item->id, ['to' => 'rework', 'notes' => 'seams'])
        ->assertOk();

    // From rework the only forward move is back to qc.
    expect($response->json('data.allowed_transitions'))->toContain('qc');
});
