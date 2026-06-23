<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\FabricMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('forbids adjust_out without the approval permission (403)', function () {
    // Inventory Manager can adjust but cannot approve adjust-out.
    $manager = makeUser($this->branch, 'Inventory Manager');
    $roll = ledgerRoll($this->branch, 20.0);

    $this->withHeaders(bearer($manager))
        ->postJson("/api/v1/inventory/fabric-rolls/{$roll->id}/adjust", [
            'type' => 'adjust_out',
            'metres' => 5,
            'reason' => 'water damage in storage',
        ])
        ->assertStatus(403)
        ->assertJsonPath('code', 'INVENTORY_APPROVAL_REQUIRED');

    expect((float) $roll->fresh()->remaining_metres)->toBe(20.0);
});

it('allows adjust_out for an approver and deducts stock', function () {
    // Admin holds inventory.fabric_rolls.adjust_out_approve.
    $admin = makeUser($this->branch, 'Admin');
    $roll = ledgerRoll($this->branch, 20.0);

    $this->withHeaders(bearer($admin))
        ->postJson("/api/v1/inventory/fabric-rolls/{$roll->id}/adjust", [
            'type' => 'adjust_out',
            'metres' => 5,
            'reason' => 'water damage in storage',
        ])
        ->assertOk();

    expect((float) $roll->fresh()->remaining_metres)->toBe(15.0)
        ->and(FabricMovement::query()->where('type', 'adjust_out')->count())->toBe(1);
});

it('requires a reason of at least 10 chars for adjust_out (422)', function () {
    $admin = makeUser($this->branch, 'Admin');
    $roll = ledgerRoll($this->branch, 20.0);

    $this->withHeaders(bearer($admin))
        ->postJson("/api/v1/inventory/fabric-rolls/{$roll->id}/adjust", [
            'type' => 'adjust_out',
            'metres' => 5,
            'reason' => 'short',
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');
});

it('lets a manager adjust stock in without approval', function () {
    $manager = makeUser($this->branch, 'Inventory Manager');
    $roll = ledgerRoll($this->branch, 20.0);

    $this->withHeaders(bearer($manager))
        ->postJson("/api/v1/inventory/fabric-rolls/{$roll->id}/adjust", [
            'type' => 'adjust_in',
            'metres' => 5,
        ])
        ->assertOk();

    expect((float) $roll->fresh()->remaining_metres)->toBe(25.0);
});
