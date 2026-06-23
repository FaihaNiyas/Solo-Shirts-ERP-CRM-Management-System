<?php

declare(strict_types=1);

use App\Modules\Identity\Models\Branch;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->approver = makeUser($this->branch, 'Admin');
    $this->roll = ledgerRoll($this->branch, 20.0);
});

function pendingReport(Branch $branch, FabricRoll $roll, float $lost = 5.0): DamageReport
{
    return DamageReport::factory()->for($branch)->create([
        'fabric_roll_id' => $roll->id,
        'quantity_lost_metres' => $lost,
        'status' => 'pending',
    ]);
}

it('a non-owner cannot approve a damage report (403)', function () {
    $report = pendingReport($this->branch, $this->roll);
    $nonApprover = makeUser($this->branch, 'Inventory Manager');

    $this->withHeaders(bearer($nonApprover) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/damage-reports/{$report->id}/approve")
        ->assertForbidden();

    expect($report->fresh()->status)->toBe('pending');
});

it('approval deducts stock via a single damage_writeoff movement', function () {
    $report = pendingReport($this->branch, $this->roll, 5.0);

    $this->withHeaders(bearer($this->approver) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/damage-reports/{$report->id}/approve", ['notes' => 'confirmed'])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    expect((float) $this->roll->fresh()->remaining_metres)->toBe(15.0)
        ->and(FabricMovement::query()->where('type', 'damage_writeoff')->where('fabric_roll_id', $this->roll->id)->count())->toBe(1)
        ->and($report->fresh()->approved_by)->toBe($this->approver->id);
});

it('rejection leaves stock untouched and records the reason', function () {
    $report = pendingReport($this->branch, $this->roll, 5.0);

    $this->withHeaders(bearer($this->approver))
        ->postJson("/api/v1/damage-reports/{$report->id}/reject", ['reason' => 'not actually damaged on review'])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    expect((float) $this->roll->fresh()->remaining_metres)->toBe(20.0)
        ->and(FabricMovement::query()->where('type', 'damage_writeoff')->count())->toBe(0)
        ->and($report->fresh()->rejection_reason)->toBe('not actually damaged on review');
});

it('returns 409 ALREADY_APPROVED when approving an approved report under a new key', function () {
    $report = pendingReport($this->branch, $this->roll, 5.0);

    $this->withHeaders(bearer($this->approver) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/damage-reports/{$report->id}/approve")
        ->assertOk();

    $this->withHeaders(bearer($this->approver) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/damage-reports/{$report->id}/approve")
        ->assertStatus(409)
        ->assertJsonPath('code', 'ALREADY_APPROVED');

    // Only one write-off happened.
    expect(FabricMovement::query()->where('type', 'damage_writeoff')->count())->toBe(1);
});

it('replays the original response for a repeated Idempotency-Key', function () {
    $report = pendingReport($this->branch, $this->roll, 5.0);
    $key = (string) Str::uuid();

    $this->withHeaders(bearer($this->approver) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/damage-reports/{$report->id}/approve")
        ->assertOk();

    $this->withHeaders(bearer($this->approver) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/damage-reports/{$report->id}/approve")
        ->assertOk();

    expect(FabricMovement::query()->where('type', 'damage_writeoff')->count())->toBe(1);
});

it('refuses to write off more than the roll has (409 INSUFFICIENT_STOCK)', function () {
    $report = pendingReport($this->branch, $this->roll, 50.0);

    $this->withHeaders(bearer($this->approver) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/damage-reports/{$report->id}/approve")
        ->assertStatus(409)
        ->assertJsonPath('code', 'INSUFFICIENT_STOCK');

    expect($report->fresh()->status)->toBe('pending')
        ->and((float) $this->roll->fresh()->remaining_metres)->toBe(20.0);
});
