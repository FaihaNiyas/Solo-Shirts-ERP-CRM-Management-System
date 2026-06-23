<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

function setUpItemInCutting(TestCase $test, User $user, Branch $branch, float $reserve): int
{
    $roll = fabricRoll($branch, 50.0);
    $item = productionItem($branch, 'draft');
    allocateFabric($test, $user, $item->id, ['roll_id' => $roll->id, 'metres' => $reserve])->assertCreated();
    $test->withHeaders(bearer($user))->postJson("/api/v1/cutting/items/{$item->id}/start-cutting")->assertOk();

    return $item->id;
}

it('forbids consuming more than reserved without the over-consume permission', function () {
    // Cutting Master can cut but lacks fabric.over_consume.
    $cuttingMaster = makeUser($this->branch, 'Cutting Master');
    $itemId = setUpItemInCutting($this, $cuttingMaster, $this->branch, 3.0);

    $this->withHeaders(bearer($cuttingMaster))
        ->postJson("/api/v1/cutting/items/{$itemId}/complete-cutting", [
            'actual_metres' => 5,
            'bundles' => [['pieces' => 4]],
        ])
        ->assertStatus(403)
        ->assertJsonPath('code', 'OVER_CONSUME_FORBIDDEN');
});

it('allows over-consume for a user holding fabric.over_consume', function () {
    // Production Supervisor holds fabric.over_consume.
    $supervisor = makeUser($this->branch, 'Production Supervisor');
    $itemId = setUpItemInCutting($this, $supervisor, $this->branch, 3.0);

    $this->withHeaders(bearer($supervisor))
        ->postJson("/api/v1/cutting/items/{$itemId}/complete-cutting", [
            'actual_metres' => 5,
            'bundles' => [['pieces' => 4]],
        ])
        ->assertOk()
        ->assertJsonPath('data.item.state', 'tailoring');
});
