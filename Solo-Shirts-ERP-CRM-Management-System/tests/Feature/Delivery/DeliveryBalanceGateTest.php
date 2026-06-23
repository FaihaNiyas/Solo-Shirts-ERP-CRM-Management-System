<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Delivery\Services\RackSlotService;
use App\Modules\Finance\Models\Payment;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * The full outstanding-balance gate applies to every fulfillment channel, not
 * only counter pickup: home delivery and courier dispatch/confirmation are
 * blocked while the order owes money, and unblocked only when the balance is
 * exactly zero. Mirrors the Front Desk HandoverService gate, reusing the same
 * BalanceService source of truth (integer paise).
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('blocks home-delivery dispatch while the order balance is pending (422 BALANCE_PENDING)', function () {
    fakeNotifications();
    $order = deliverableOrder($this->branch, 1);
    $item = $order->items()->sole();
    makeInvoice($this->branch, $order, ['total_paise' => 500000]);
    $delivery = makeDelivery($this->branch, $order, ['mode' => Delivery::MODE_HOME, 'status' => Delivery::STATUS_SCHEDULED]);

    dispatchDelivery($this, $this->staff, $delivery->id)
        ->assertStatus(422)
        ->assertJsonPath('code', 'BALANCE_PENDING');

    // No side effects: not dispatched, item still ready, not delivered.
    expect($delivery->fresh()->status)->toBe(Delivery::STATUS_SCHEDULED)
        ->and((string) $item->fresh()->state)->toBe(OrderItem::STATE_READY_FOR_DELIVERY);
});

it('blocks courier dispatch while the order balance is pending (422 BALANCE_PENDING)', function () {
    fakeNotifications();
    $order = deliverableOrder($this->branch, 1);
    makeInvoice($this->branch, $order, ['total_paise' => 500000]);
    $delivery = makeDelivery($this->branch, $order, ['mode' => Delivery::MODE_COURIER, 'status' => Delivery::STATUS_SCHEDULED]);

    dispatchDelivery($this, $this->staff, $delivery->id)
        ->assertStatus(422)
        ->assertJsonPath('code', 'BALANCE_PENDING');

    expect($delivery->fresh()->status)->toBe(Delivery::STATUS_SCHEDULED);
});

it('blocks home-delivery confirmation while balance pending — no delivered, no rack release', function () {
    fakeNotifications();
    $order = deliverableOrder($this->branch, 1);
    $item = $order->items()->sole();
    rackSlot($this->branch, 'R-A-01');
    app(RackSlotService::class)->assign($item->id, 'R-A-01', null);
    makeInvoice($this->branch, $order, ['total_paise' => 500000]);
    $delivery = makeDelivery($this->branch, $order, [
        'mode' => Delivery::MODE_HOME,
        'status' => Delivery::STATUS_DISPATCHED,
        'dispatched_at' => now(),
    ]);

    confirmDelivery($this, $this->staff, $delivery->id, '000000')
        ->assertStatus(422)
        ->assertJsonPath('code', 'BALANCE_PENDING');

    expect($delivery->fresh()->status)->toBe(Delivery::STATUS_DISPATCHED)
        ->and((string) $item->fresh()->state)->toBe(OrderItem::STATE_READY_FOR_DELIVERY)
        ->and(RackSlot::query()->where('slot_code', 'R-A-01')->value('current_order_item_id'))->toBe($item->id);
});

it('still blocks delivery after only a partial payment', function () {
    fakeNotifications();
    $order = deliverableOrder($this->branch, 1);
    $invoice = makeInvoice($this->branch, $order, ['total_paise' => 500000]);
    Payment::factory()->for($this->branch)->create(['invoice_id' => $invoice->id, 'amount_paise' => 100000]);
    $delivery = makeDelivery($this->branch, $order, ['mode' => Delivery::MODE_HOME, 'status' => Delivery::STATUS_SCHEDULED]);

    dispatchDelivery($this, $this->staff, $delivery->id)
        ->assertStatus(422)
        ->assertJsonPath('code', 'BALANCE_PENDING');
});

it('allows home-delivery dispatch and OTP confirmation once the balance is fully paid', function () {
    $fake = fakeNotifications();
    $order = deliverableOrder($this->branch, 1);
    $item = $order->items()->sole();
    rackSlot($this->branch, 'R-A-01');
    app(RackSlotService::class)->assign($item->id, 'R-A-01', null);
    $invoice = makeInvoice($this->branch, $order, ['total_paise' => 500000]);
    Payment::factory()->for($this->branch)->create(['invoice_id' => $invoice->id, 'amount_paise' => 500000]);
    $delivery = makeDelivery($this->branch, $order, ['mode' => Delivery::MODE_HOME, 'status' => Delivery::STATUS_SCHEDULED]);

    dispatchDelivery($this, $this->staff, $delivery->id)->assertOk();

    confirmDelivery($this, $this->staff, $delivery->id, (string) $fake->lastOtp())
        ->assertOk()
        ->assertJsonPath('data.status', Delivery::STATUS_DELIVERED);

    expect((string) $item->fresh()->state)->toBe(OrderItem::STATE_DELIVERED)
        ->and(RackSlot::query()->where('slot_code', 'R-A-01')->value('current_order_item_id'))->toBeNull();
});

it('returns 404 confirming a delivery that belongs to another branch', function () {
    fakeNotifications();
    $other = makeBranch(['code' => 'BR2']);
    $order = deliverableOrder($other, 1);
    makeInvoice($other, $order, ['total_paise' => 500000]);
    $delivery = makeDelivery($other, $order, ['status' => Delivery::STATUS_DISPATCHED, 'dispatched_at' => now()]);

    // $this->staff is scoped to HQ → the BR2 delivery is invisible (404) before
    // any balance check runs.
    confirmDelivery($this, $this->staff, $delivery->id, '000000')->assertStatus(404);
});
