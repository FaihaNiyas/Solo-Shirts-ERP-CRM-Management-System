<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Exceptions\ProductionException;
use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Production\Services\StateTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $this->service = app(StateTransitionService::class);
});

/**
 * Seed a number of prior Rework visits for an item already sitting in QC.
 */
function seedReworkVisits(OrderItem $item, int $count): void
{
    ProductionTransition::factory()->count($count)->create([
        'order_item_id' => $item->id,
        'branch_id' => $item->branch_id,
        'from_state' => 'qc',
        'to_state' => 'rework',
    ]);
}

it('blocks a 4th rework without the override permission', function () {
    $worker = User::factory()->create(['branch_id' => $this->branch->id]);
    $worker->givePermissionTo('production.transition.rework');

    $item = productionItem($this->branch, 'qc');
    seedReworkVisits($item, StateTransitionService::MAX_REWORK_VISITS);

    expect(fn () => $this->service->transition(
        $item->id,
        'rework',
        $worker,
        (string) Str::uuid(),
        'again',
    ))->toThrow(ProductionException::class, 'rework limit');

    expect((string) $item->fresh()->state)->toBe('qc');
});

it('allows the override holder (QC Supervisor) to exceed the rework limit', function () {
    $supervisor = makeUser($this->branch, 'QC Supervisor');

    $item = productionItem($this->branch, 'qc');
    seedReworkVisits($item, StateTransitionService::MAX_REWORK_VISITS);

    $result = $this->service->transition(
        $item->id,
        'rework',
        $supervisor,
        (string) Str::uuid(),
        'supervisor override',
    );

    expect((string) $result->state)->toBe('rework');
});
