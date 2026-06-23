<?php

declare(strict_types=1);

use App\Modules\Production\Models\TailorAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->supervisor = makeUser($this->branch, 'Production Supervisor');
    $this->tailorA = makeUser($this->branch, 'Tailor');
    $this->tailorB = makeUser($this->branch, 'Tailor');
});

it('rejects a second active assignment on the same bundle (partial unique)', function () {
    $bundle = cutBundleFor($this->branch);

    $this->withHeaders(bearer($this->supervisor))
        ->postJson('/api/v1/tailoring/assignments', [
            'bundle_id' => $bundle->id,
            'tailor_id' => $this->tailorA->id,
        ])->assertCreated();

    $this->withHeaders(bearer($this->supervisor))
        ->postJson('/api/v1/tailoring/assignments', [
            'bundle_id' => $bundle->id,
            'tailor_id' => $this->tailorB->id,
        ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'DUPLICATE_ACTIVE_ASSIGNMENT');

    expect(TailorAssignment::query()->where('bundle_id', $bundle->id)->count())->toBe(1);
});

it('allows a new assignment after the previous one is reassigned away', function () {
    $bundle = cutBundleFor($this->branch);

    $first = TailorAssignment::factory()->create([
        'bundle_id' => $bundle->id,
        'order_item_id' => $bundle->order_item_id,
        'branch_id' => $this->branch->id,
        'tailor_id' => $this->tailorA->id,
        'status' => 'reassigned',
    ]);

    // The reassigned row's active_bundle_id is NULL, so this active insert is fine.
    $this->withHeaders(bearer($this->supervisor))
        ->postJson('/api/v1/tailoring/assignments', [
            'bundle_id' => $bundle->id,
            'tailor_id' => $this->tailorB->id,
        ])->assertCreated();

    expect($first->fresh()->status)->toBe('reassigned');
});
