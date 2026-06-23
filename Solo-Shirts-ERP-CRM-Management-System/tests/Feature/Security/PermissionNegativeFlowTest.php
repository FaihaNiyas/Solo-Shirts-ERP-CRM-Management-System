<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Consolidated negative-permission + branch-isolation flow (QA-006 / Task 8).
 * Every request is a real authenticated API call — no middleware disabled, no
 * policy bypassed. Each denial must come back as the standard error envelope
 * (success=false) carrying a request_id.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branchA = makeBranch(['code' => 'HQ']);
    $this->branchB = makeBranch(['code' => 'BR2']);
});

/** Assert a forbidden response keeps the standard envelope with a request_id. */
function assertForbiddenEnvelope(TestResponse $r): void
{
    $r->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('request_id', fn ($id) => is_string($id) && $id !== '');
}

it('forbids a Tailor from reading finance invoices (403)', function () {
    $tailor = makeUser($this->branchA, 'Tailor');

    assertForbiddenEnvelope(
        $this->withHeaders(bearer($tailor))->getJson('/api/v1/finance/invoices')
    );
});

it('forbids Front Desk from approving a measurement version (403)', function () {
    $frontDesk = makeUser($this->branchA, 'Front Desk');
    $customer = Customer::factory()->for($this->branchA)->create();
    $version = approvedVersionFor($this->branchA, $customer);

    assertForbiddenEnvelope(
        $this->withHeaders(bearer($frontDesk) + ['Idempotency-Key' => (string) Str::uuid()])
            ->postJson("/api/v1/measurements/versions/{$version->id}/approve")
    );
});

it('forbids an Inventory Manager from creating an invoice (403)', function () {
    $inventory = makeUser($this->branchA, 'Inventory Manager');
    $order = deliverableOrder($this->branchA);

    assertForbiddenEnvelope(
        $this->withHeaders(bearer($inventory) + ['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/api/v1/finance/invoices', invoicePayload($order->id))
    );
});

it('forbids an Accountant from running a production transition (403)', function () {
    $accountant = makeUser($this->branchA, 'Accountant');
    $item = productionItem($this->branchA);

    assertForbiddenEnvelope(
        $this->withHeaders(bearer($accountant) + ['Idempotency-Key' => (string) Str::uuid()])
            ->postJson("/api/v1/production/items/{$item->id}/transition", ['to' => 'fabric_allocated'])
    );
});

it('shares a customer across branches (customers are global, not branch-isolated)', function () {
    $staffA = makeUser($this->branchA, 'Front Desk');
    $customerB = Customer::factory()->for($this->branchB)->create();

    // Customers are deliberately global: a walk-in can be served at any branch, so
    // branch-scoped staff can open a customer registered elsewhere. (Orders remain
    // branch-scoped to the branch that took them.)
    $this->withHeaders(bearer($staffA))
        ->getJson("/api/v1/customers/{$customerB->id}")
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('forbids non-Owner staff from switching branch (403)', function () {
    $staffA = makeUser($this->branchA, 'Front Desk');

    assertForbiddenEnvelope(
        $this->withHeaders(bearer($staffA))
            ->postJson('/api/v1/auth/switch-branch', ['branch_id' => $this->branchB->id])
    );
});
