<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Delivery\Services\RackSlotService;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('confirms with the correct OTP, delivers the items and frees their rack slots', function () {
    $fake = fakeNotifications();

    $order = deliverableOrder($this->branch, 1);
    /** @var OrderItem $item */
    $item = $order->items()->sole();

    // The item is sitting on a rack slot, as it would be after ready-for-delivery.
    rackSlot($this->branch, 'R-A-01');
    app(RackSlotService::class)->assign($item->id, 'R-A-01', null);

    $delivery = makeDelivery($this->branch, $order);

    dispatchDelivery($this, $this->staff, $delivery->id)->assertOk();

    confirmDelivery($this, $this->staff, $delivery->id, (string) $fake->lastOtp())
        ->assertOk()
        ->assertJsonPath('data.status', Delivery::STATUS_DELIVERED);

    expect((string) $item->fresh()->state)->toBe(OrderItem::STATE_DELIVERED);

    /** @var RackSlot $slot */
    $slot = RackSlot::query()->where('slot_code', 'R-A-01')->sole();
    expect($slot->current_order_item_id)->toBeNull();

    $assignment = RackAssignment::query()->where('order_item_id', $item->id)->sole();
    expect($assignment->released_at)->not->toBeNull();
});

it('rejects confirmation before dispatch with 409 NOT_DISPATCHED', function () {
    fakeNotifications();
    $delivery = makeDelivery($this->branch);

    confirmDelivery($this, $this->staff, $delivery->id, '123456')
        ->assertStatus(409)
        ->assertJsonPath('code', 'NOT_DISPATCHED');
});

it('rejects confirmation on a cancelled delivery with 409 DELIVERY_CANCELLED', function () {
    fakeNotifications();
    $delivery = makeDelivery($this->branch, null, ['status' => Delivery::STATUS_CANCELLED]);

    confirmDelivery($this, $this->staff, $delivery->id, '123456')
        ->assertStatus(409)
        ->assertJsonPath('code', 'DELIVERY_CANCELLED');
});
