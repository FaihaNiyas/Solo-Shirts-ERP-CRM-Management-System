<?php

declare(strict_types=1);

use App\Modules\Production\Events\OrderItemStateChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Production Supervisor');
});

it('dispatches OrderItemStateChanged exactly once per successful transition', function () {
    Event::fake([OrderItemStateChanged::class]);

    $item = productionItem($this->branch, 'cutting');

    transitionItem($this, $this->user, $item->id, ['to' => 'tailoring'])->assertOk();

    Event::assertDispatchedTimes(OrderItemStateChanged::class, 1);
    Event::assertDispatched(OrderItemStateChanged::class, function (OrderItemStateChanged $event) use ($item) {
        return $event->orderItemId === $item->id
            && $event->from === 'cutting'
            && $event->to === 'tailoring';
    });
});

it('does not dispatch the event when a transition is rejected', function () {
    Event::fake([OrderItemStateChanged::class]);

    $item = productionItem($this->branch, 'cutting');

    transitionItem($this, $this->user, $item->id, ['to' => 'delivered'])->assertStatus(409);

    Event::assertNotDispatched(OrderItemStateChanged::class);
});

it('writes an audit log entry via the registered listener', function () {
    $item = productionItem($this->branch, 'cutting');

    transitionItem($this, $this->user, $item->id, ['to' => 'tailoring'])->assertOk();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'production',
        'event' => 'state-changed',
        'subject_type' => $item->getMorphClass(),
        'subject_id' => $item->id,
    ]);
});
