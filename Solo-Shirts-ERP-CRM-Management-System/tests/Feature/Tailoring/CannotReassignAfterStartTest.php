<?php

declare(strict_types=1);

use App\Modules\Production\Models\TailorAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->supervisor = makeUser($this->branch, 'Production Supervisor');
    $this->tailor = makeUser($this->branch, 'Tailor');
    $this->otherTailor = makeUser($this->branch, 'Tailor');
});

it('reassigns an unstarted assignment to another tailor', function () {
    $bundle = cutBundleFor($this->branch);
    $assignment = TailorAssignment::factory()->create([
        'bundle_id' => $bundle->id,
        'order_item_id' => $bundle->order_item_id,
        'branch_id' => $this->branch->id,
        'tailor_id' => $this->tailor->id,
    ]);

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/tailoring/assignments/{$assignment->id}/reassign", [
            'tailor_id' => $this->otherTailor->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.tailor_id', $this->otherTailor->id)
        ->assertJsonPath('data.status', 'assigned');

    expect($assignment->fresh()->status)->toBe('reassigned')
        // The partial-unique still allows exactly one active assignment per bundle.
        ->and(TailorAssignment::query()->where('bundle_id', $bundle->id)
            ->whereIn('status', ['assigned', 'in_progress', 'completed'])->count())->toBe(1);
});

it('cannot reassign once the assignment has started (409)', function () {
    $bundle = cutBundleFor($this->branch);
    $assignment = TailorAssignment::factory()->started()->create([
        'bundle_id' => $bundle->id,
        'order_item_id' => $bundle->order_item_id,
        'branch_id' => $this->branch->id,
        'tailor_id' => $this->tailor->id,
    ]);

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/tailoring/assignments/{$assignment->id}/reassign", [
            'tailor_id' => $this->otherTailor->id,
        ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ASSIGNMENT_ALREADY_STARTED');
});

it('records an audit entry on reassign', function () {
    $bundle = cutBundleFor($this->branch);
    $assignment = TailorAssignment::factory()->create([
        'bundle_id' => $bundle->id,
        'order_item_id' => $bundle->order_item_id,
        'branch_id' => $this->branch->id,
        'tailor_id' => $this->tailor->id,
    ]);

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/tailoring/assignments/{$assignment->id}/reassign", [
            'tailor_id' => $this->otherTailor->id,
        ])->assertCreated();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'tailoring',
        'event' => 'reassigned',
    ]);
});
