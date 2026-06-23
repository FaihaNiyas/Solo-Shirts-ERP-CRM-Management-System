<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('replays the prior result for a repeated confirm with the same Idempotency-Key', function () {
    $fake = fakeNotifications();
    $order = deliverableOrder($this->branch, 1);
    /** @var OrderItem $item */
    $item = $order->items()->sole();
    $delivery = makeDelivery($this->branch, $order);

    dispatchDelivery($this, $this->staff, $delivery->id)->assertOk();
    $otp = (string) $fake->lastOtp();

    $key = (string) Str::uuid();

    $first = confirmDelivery($this, $this->staff, $delivery->id, $otp, $key)
        ->assertOk()
        ->assertJsonPath('data.status', Delivery::STATUS_DELIVERED);

    // Same key → cached response replayed, not re-executed (which would 409).
    $second = confirmDelivery($this, $this->staff, $delivery->id, $otp, $key)
        ->assertOk()
        ->assertJsonPath('data.status', Delivery::STATUS_DELIVERED);

    expect($second->json('data.id'))->toBe($first->json('data.id'));

    // The item transitioned to Delivered exactly once.
    $delivered = ProductionTransition::query()
        ->where('order_item_id', $item->id)
        ->where('to_state', OrderItem::STATE_DELIVERED)
        ->count();
    expect($delivered)->toBe(1);
});
