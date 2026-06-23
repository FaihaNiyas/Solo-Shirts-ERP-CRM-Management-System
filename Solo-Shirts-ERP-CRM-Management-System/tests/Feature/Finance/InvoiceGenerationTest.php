<?php

declare(strict_types=1);

use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->accountant = makeUser($this->branch, 'Accountant');
});

it('generates an invoice from an order with GST and delivery charges in the total', function () {
    $order = deliverableOrder($this->branch);
    $order->update(['delivery_charges_paise' => 15000]);

    $response = $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => 'inv-gen-success'])
        ->postJson('/api/v1/finance/invoices', [
            'order_id' => $order->id,
            'gst_treatment' => 'regular',
            'inter_state' => false,
            'discount_paise' => 5000,
            'lines' => [
                ['description' => 'Shirt stitching', 'hsn_code' => '9988', 'quantity' => 2, 'unit_price_paise' => 50000, 'gst_rate' => 5],
            ],
        ])
        ->assertCreated();

    // taxable 100000, GST 5% = 5000 (2500+2500), +15000 delivery −5000 discount = 115000.
    $response->assertJsonPath('data.subtotal_paise', 100000)
        ->assertJsonPath('data.cgst_paise', 2500)
        ->assertJsonPath('data.sgst_paise', 2500)
        ->assertJsonPath('data.total_paise', 115000)
        ->assertJsonPath('data.status', 'issued');

    expect($response->json('data.invoice_no'))->toContain('-INV-');

    $this->assertDatabaseHas('invoice_lines', [
        'description' => 'Shirt stitching',
        'taxable_paise' => 100000,
        'tax_paise' => 5000,
    ]);
});

it('refuses to invoice a fully cancelled order with 409 ORDER_CANCELLED', function () {
    $order = deliverableOrder($this->branch, 1, OrderItem::STATE_CANCELLED);

    $this->withHeaders(bearer($this->accountant) + ['Idempotency-Key' => 'inv-gen-cancelled'])
        ->postJson('/api/v1/finance/invoices', [
            'order_id' => $order->id,
            'gst_treatment' => 'regular',
            'lines' => [
                ['description' => 'Shirt', 'quantity' => 1, 'unit_price_paise' => 50000, 'gst_rate' => 5],
            ],
        ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'ORDER_CANCELLED');
});
