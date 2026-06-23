<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\FabricMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->reporter = makeUser($this->branch, 'Inventory Manager');
});

it('creates a pending damage report without touching stock', function () {
    $roll = ledgerRoll($this->branch, 20.0);

    $this->withHeaders(bearer($this->reporter))
        ->postJson('/api/v1/damage-reports', [
            'fabric_roll_id' => $roll->id,
            'stage' => 'cutting',
            'damage_type' => 'tear',
            'quantity_lost_metres' => 3,
            'action_taken' => 'segregated',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    expect(DamageReport::query()->where('status', 'pending')->count())->toBe(1)
        // No deduction happens at report time.
        ->and((float) $roll->fresh()->remaining_metres)->toBe(20.0)
        ->and(FabricMovement::query()->where('type', 'damage_writeoff')->count())->toBe(0);
});

it('requires damage_type_other when damage_type is other (422)', function () {
    $roll = ledgerRoll($this->branch, 20.0);

    $this->withHeaders(bearer($this->reporter))
        ->postJson('/api/v1/damage-reports', [
            'fabric_roll_id' => $roll->id,
            'stage' => 'cutting',
            'damage_type' => 'other',
            'quantity_lost_metres' => 3,
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');
});

it('forbids reporting without the create permission', function () {
    $roll = ledgerRoll($this->branch, 20.0);
    $stranger = makeUser($this->branch, 'Front Desk');

    $this->withHeaders(bearer($stranger))
        ->postJson('/api/v1/damage-reports', [
            'fabric_roll_id' => $roll->id,
            'stage' => 'cutting',
            'damage_type' => 'tear',
            'quantity_lost_metres' => 3,
        ])
        ->assertForbidden();
});
