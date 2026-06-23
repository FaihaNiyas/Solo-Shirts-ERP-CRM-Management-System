<?php

declare(strict_types=1);

use App\Modules\Production\Models\ProductionStageSupervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->manager = makeUser($this->branch, 'Production Supervisor');
    $this->tailor = makeUser($this->branch, 'Tailor', ['name' => 'Aman Tailor']);
});

it('assigns a user as the supervisor of a section', function () {
    $this->withHeaders(bearer($this->manager))
        ->postJson('/api/v1/production/stage-supervisors', [
            'user_id' => $this->tailor->id,
            'stage' => 'tailoring',
        ])
        ->assertCreated()
        ->assertJsonPath('data.stage', 'tailoring')
        ->assertJsonPath('data.user_name', 'Aman Tailor');

    expect(ProductionStageSupervisor::query()
        ->where('user_id', $this->tailor->id)
        ->where('stage', 'tailoring')
        ->exists())->toBeTrue();
});

it('is idempotent — assigning the same user/stage twice keeps one row', function () {
    $payload = ['user_id' => $this->tailor->id, 'stage' => 'tailoring'];

    $this->withHeaders(bearer($this->manager))->postJson('/api/v1/production/stage-supervisors', $payload)->assertCreated();
    $this->withHeaders(bearer($this->manager))->postJson('/api/v1/production/stage-supervisors', $payload)->assertCreated();

    expect(ProductionStageSupervisor::query()->where('user_id', $this->tailor->id)->count())->toBe(1);
});

it('rejects an invalid section stage (422)', function () {
    $this->withHeaders(bearer($this->manager))
        ->postJson('/api/v1/production/stage-supervisors', [
            'user_id' => $this->tailor->id,
            'stage' => 'delivered', // not a supervisable section
        ])
        ->assertStatus(422);
});

it('lists and unassigns supervisors', function () {
    $assignment = ProductionStageSupervisor::query()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->tailor->id,
        'stage' => 'tailoring',
    ]);

    $this->withHeaders(bearer($this->manager))
        ->getJson('/api/v1/production/stage-supervisors')
        ->assertOk()
        ->assertJsonPath('data.0.stage', 'tailoring');

    $this->withHeaders(bearer($this->manager))
        ->deleteJson("/api/v1/production/stage-supervisors/{$assignment->id}")
        ->assertOk();

    expect(ProductionStageSupervisor::query()->whereKey($assignment->id)->exists())->toBeFalse();
});

it('forbids Front Desk from assigning supervisors (403)', function () {
    $frontDesk = makeUser($this->branch, 'Front Desk');

    $this->withHeaders(bearer($frontDesk))
        ->postJson('/api/v1/production/stage-supervisors', [
            'user_id' => $this->tailor->id,
            'stage' => 'tailoring',
        ])
        ->assertForbidden();
});

it('returns the stages a user supervises via my-sections', function () {
    ProductionStageSupervisor::query()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->tailor->id,
        'stage' => 'tailoring',
    ]);

    $this->withHeaders(bearer($this->tailor))
        ->getJson('/api/v1/production/my-sections')
        ->assertOk()
        ->assertJsonPath('data.stages', ['tailoring']);
});

it('shows the assigned supervisor on the board card', function () {
    ProductionStageSupervisor::query()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->tailor->id,
        'stage' => 'tailoring',
    ]);
    productionItem($this->branch, 'tailoring');

    // tailoring is index 3 in WORKFLOW_STATES.
    $this->withHeaders(bearer($this->manager))
        ->getJson('/api/v1/production/board')
        ->assertOk()
        ->assertJsonPath('data.columns.3.state', 'tailoring')
        ->assertJsonPath('data.columns.3.items.0.assigned_supervisor', 'Aman Tailor');
});

it('scopes the board to my section when mine=true', function () {
    ProductionStageSupervisor::query()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->tailor->id,
        'stage' => 'tailoring',
    ]);
    productionItem($this->branch, 'tailoring');
    productionItem($this->branch, 'cutting');

    $res = $this->withHeaders(bearer($this->tailor))
        ->getJson('/api/v1/production/board?mine=1')
        ->assertOk();

    // tailoring (index 3) keeps its item; cutting (index 2) is excluded.
    expect($res->json('data.columns.3.count'))->toBe(1)
        ->and($res->json('data.columns.2.count'))->toBe(0);
});
