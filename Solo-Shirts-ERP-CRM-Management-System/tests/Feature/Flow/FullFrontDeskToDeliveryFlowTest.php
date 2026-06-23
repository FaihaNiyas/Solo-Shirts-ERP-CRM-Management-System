<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * End-to-end happy path (Task 6): a customer is created at the front desk, an
 * order flows through the full production pipeline, is invoiced and paid, then
 * dispatched and confirmed by OTP — all via real authenticated API calls, with
 * Idempotency-Key on every write the backend requires it on. The front-desk
 * step runs as Front Desk (proving the role can start the flow); the downstream
 * mechanical steps run as Owner (authorised for every stage, no policy bypass).
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->frontDesk = makeUser($this->branch, 'Front Desk');
    $this->owner = makeUser($this->branch, 'Owner');
    // A free rack slot so the auto-assign listener can place the item when it
    // reaches ready_for_delivery.
    rackSlot($this->branch, 'R-A1');
});

it('runs the whole flow from customer creation to OTP-confirmed delivery', function () {
    $fake = fakeNotifications();

    // 1) Front Desk creates a customer.
    $customerResp = $this->withHeaders(bearer($this->frontDesk))
        ->postJson('/api/v1/customers', ['name' => 'Asha Verma', 'phone' => '9876500011'])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('request_id', fn ($id) => is_string($id) && $id !== '');

    $customerId = $customerResp->json('data.id');
    expect($customerResp->json('data.customer_code'))->toBeString()->not->toBeEmpty();

    // An approved measurement version for that customer (measurement approval has
    // its own dedicated test; here it is a precondition for ordering).
    $version = approvedVersionFor($this->branch, Customer::query()->findOrFail($customerId));

    // 2) Create the order (idempotent) with one shirt bound to the approved version.
    // A received order enters production at Fabric Ready (no manual fabric step).
    $orderResp = $this->withHeaders(bearer($this->frontDesk) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($customerId, $version->id))
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.items.0.state', 'fabric_allocated');

    $orderId = $orderResp->json('data.id');
    $itemId = $orderResp->json('data.items.0.id');

    // 3) Walk the item through every production state to ready_for_delivery
    // (it already starts at fabric_allocated).
    $pipeline = ['cutting', 'tailoring', 'kaja_button', 'finishing', 'qc', 'packing', 'ready_for_delivery'];
    foreach ($pipeline as $state) {
        transitionItem($this, $this->owner, $itemId, ['to' => $state])
            ->assertOk()
            ->assertJsonPath('data.state', $state);
    }

    // 4) Invoice the order (idempotent) and 5) record full payment (idempotent).
    $invoiceResp = $this->withHeaders(bearer($this->owner) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/finance/invoices', invoicePayload($orderId))
        ->assertCreated()
        ->assertJsonPath('data.status', 'issued')
        ->assertJsonPath('request_id', fn ($id) => is_string($id) && $id !== '');

    $invoiceId = $invoiceResp->json('data.id');
    $total = $invoiceResp->json('data.total_paise');

    $this->withHeaders(bearer($this->owner) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/finance/payments', [
            'invoice_id' => $invoiceId, 'method' => 'cash', 'amount_paise' => $total,
        ])
        ->assertCreated();

    // 6) Create the delivery, 7) dispatch it (issues an OTP), 8) confirm by OTP.
    $deliveryResp = $this->withHeaders(bearer($this->owner))
        ->postJson('/api/v1/deliveries', ['order_id' => $orderId, 'mode' => 'pickup'])
        ->assertCreated();
    $deliveryId = $deliveryResp->json('data.id');

    dispatchDelivery($this, $this->owner, $deliveryId)->assertOk();

    confirmDelivery($this, $this->owner, $deliveryId, (string) $fake->lastOtp())
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'delivered')
        ->assertJsonPath('request_id', fn ($id) => is_string($id) && $id !== '');

    // 9) The item is delivered and its rack slot has been released.
    expect((string) OrderItem::query()->findOrFail($itemId)->state)->toBe(OrderItem::STATE_DELIVERED);

    $this->withHeaders(bearer($this->owner))
        ->getJson("/api/v1/rack/items/{$itemId}/current-slot")
        ->assertOk()
        ->assertJsonPath('data', null);
});
