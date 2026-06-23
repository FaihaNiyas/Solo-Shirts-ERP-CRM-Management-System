<?php

declare(strict_types=1);

use App\Modules\Production\Jobs\NotifyDelayedItemsJob;
use App\Modules\Production\Models\ProductionNotification;
use App\Modules\Production\Models\ProductionStageSupervisor;
use App\Modules\Production\Services\ProductionNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->manager = makeUser($this->branch, 'Production Supervisor');
    $this->sup = makeUser($this->branch, 'Tailor', ['name' => 'Section Lead']);
});

function assignSupervisor($branch, $user, string $stage): void
{
    ProductionStageSupervisor::query()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'stage' => $stage,
    ]);
}

it('notifies the destination section supervisor on a stage move', function () {
    assignSupervisor($this->branch, $this->sup, 'tailoring');
    $item = productionItem($this->branch, 'cutting');

    transitionItem($this, $this->manager, $item->id, ['to' => 'tailoring'])->assertOk();

    $this->withHeaders(bearer($this->sup))
        ->getJson('/api/v1/production/notifications')
        ->assertOk()
        ->assertJsonPath('data.unread_count', 1)
        ->assertJsonPath('data.items.0.type', ProductionNotification::TYPE_NEW_ASSIGNMENT)
        ->assertJsonPath('data.items.0.order_item_id', $item->id);
});

it('notifies the rework supervisor with a qc_failed notification', function () {
    assignSupervisor($this->branch, $this->sup, 'rework');
    $item = productionItem($this->branch, 'qc');

    transitionItem($this, $this->manager, $item->id, ['to' => 'rework', 'notes' => 'loose seams'])->assertOk();

    $count = ProductionNotification::query()
        ->where('user_id', $this->sup->id)
        ->where('type', ProductionNotification::TYPE_QC_FAILED)
        ->count();

    expect($count)->toBe(1);
});

it('notifies the section supervisor when an issue is reported', function () {
    assignSupervisor($this->branch, $this->sup, 'cutting');
    $item = productionItem($this->branch, 'cutting');

    $this->withHeaders(bearer($this->manager))
        ->postJson("/api/v1/production/items/{$item->id}/issues", [
            'issue_type' => 'machine_problem',
            'description' => 'Needle broke',
        ])->assertCreated();

    expect(ProductionNotification::query()
        ->where('user_id', $this->sup->id)
        ->where('type', ProductionNotification::TYPE_ISSUE_REPORTED)
        ->exists())->toBeTrue();
});

it('does not notify the actor when they supervise the destination', function () {
    // The manager supervises kaja_button AND triggers the move → excluded.
    assignSupervisor($this->branch, $this->manager, 'kaja_button');
    $item = productionItem($this->branch, 'tailoring');

    transitionItem($this, $this->manager, $item->id, ['to' => 'kaja_button'])->assertOk();

    $this->withHeaders(bearer($this->manager))
        ->getJson('/api/v1/production/notifications')
        ->assertOk()
        ->assertJsonPath('data.unread_count', 0);
});

it('marks a notification read and clears the unread count', function () {
    $n = ProductionNotification::query()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->sup->id,
        'type' => ProductionNotification::TYPE_NEW_ASSIGNMENT,
        'title' => 'New item',
    ]);

    $this->withHeaders(bearer($this->sup))
        ->postJson("/api/v1/production/notifications/{$n->id}/read")
        ->assertOk()
        ->assertJsonPath('data.is_read', true);

    $this->withHeaders(bearer($this->sup))
        ->getJson('/api/v1/production/notifications')
        ->assertOk()
        ->assertJsonPath('data.unread_count', 0);
});

it('cannot read another user\'s notification (404)', function () {
    $other = makeUser($this->branch, 'Tailor');
    $n = ProductionNotification::query()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $other->id,
        'type' => ProductionNotification::TYPE_NEW_ASSIGNMENT,
        'title' => 'New item',
    ]);

    $this->withHeaders(bearer($this->sup))
        ->postJson("/api/v1/production/notifications/{$n->id}/read")
        ->assertNotFound();
});

it('notifies supervisors of delayed items via the daily job, deduped', function () {
    assignSupervisor($this->branch, $this->sup, 'finishing');
    $item = productionItem($this->branch, 'finishing');
    $item->order->update(['expected_delivery_date' => now()->subDays(2)->toDateString()]);

    (new NotifyDelayedItemsJob)->handle(app(ProductionNotifier::class));
    (new NotifyDelayedItemsJob)->handle(app(ProductionNotifier::class)); // dedupe — still one

    expect(ProductionNotification::query()
        ->where('user_id', $this->sup->id)
        ->where('type', ProductionNotification::TYPE_DELAYED)
        ->count())->toBe(1);
});
