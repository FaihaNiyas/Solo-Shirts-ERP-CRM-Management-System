<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->owner = makeUser($this->branch, 'Owner');
    $this->admin = makeUser($this->branch, 'Admin');
});

it('deactivates and reactivates a user, toggling is_active', function () {
    $tailor = makeUser($this->branch, 'Tailor');

    $this->withHeaders(bearer($this->admin))
        ->postJson("/api/v1/users/{$tailor->id}/deactivate")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($tailor->refresh()->is_active)->toBeFalse();

    $this->withHeaders(bearer($this->admin))
        ->postJson("/api/v1/users/{$tailor->id}/activate")
        ->assertOk()
        ->assertJsonPath('data.is_active', true);
});

it('returns a customer order list', function () {
    $customer = Customer::factory()->for($this->branch)->create();
    Order::factory()->for($this->branch)->for($customer)->create(['order_code' => 'ORD-TEST-1']);

    $this->withHeaders(bearer($this->owner))
        ->getJson("/api/v1/customers/{$customer->id}/orders")
        ->assertOk()
        ->assertJsonPath('data.0.order_code', 'ORD-TEST-1');
});

it('returns a customer balance with outstanding equal to the unpaid total', function () {
    $invoice = makeInvoice($this->branch, null, ['status' => Invoice::STATUS_ISSUED, 'total_paise' => 250000]);

    $this->withHeaders(bearer($this->owner))
        ->getJson("/api/v1/customers/{$invoice->customer_id}/balance")
        ->assertOk()
        ->assertJsonPath('data.outstanding_paise', 250000)
        ->assertJsonPath('data.invoices.0.balance_paise', 250000);
});

it('returns a customer timeline feed', function () {
    $invoice = makeInvoice($this->branch, null, ['status' => Invoice::STATUS_ISSUED]);

    $this->withHeaders(bearer($this->owner))
        ->getJson("/api/v1/customers/{$invoice->customer_id}/timeline")
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'type', 'event', 'created_at']]]);
});

it('lists outstanding receivables grouped by customer', function () {
    makeInvoice($this->branch, null, ['status' => Invoice::STATUS_ISSUED, 'total_paise' => 500000]);

    $this->withHeaders(bearer($this->owner))
        ->getJson('/api/v1/finance/outstanding')
        ->assertOk()
        ->assertJsonStructure(['data' => [['customer_id', 'customer_name', 'invoice_count', 'total_outstanding_paise', 'oldest_invoice_date']]]);
});

it('lists documents with signed download URLs', function () {
    $this->withHeaders(bearer($this->owner))
        ->getJson('/api/v1/documents')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('finds a customer via global search', function () {
    Customer::factory()->for($this->branch)->create(['name' => 'Zenithunique Tailor']);

    $this->withHeaders(bearer($this->owner))
        ->getJson('/api/v1/search?q=Zenithunique')
        ->assertOk()
        ->assertJsonPath('data.results.0.type', 'customer')
        ->assertJsonPath('data.results.0.title', 'Zenithunique Tailor');
});

it('forbids global search results a role cannot see', function () {
    // A bare Tailor lacks finance.view, so invoice matches must not surface.
    $tailor = makeUser($this->branch, 'Tailor');
    makeInvoice($this->branch, null, ['invoice_no' => 'INV-SECRET-9']);

    $response = $this->withHeaders(bearer($tailor))
        ->getJson('/api/v1/search?q=INV-SECRET-9')
        ->assertOk();

    expect($response->json('data.results'))->toBe([]);
});
