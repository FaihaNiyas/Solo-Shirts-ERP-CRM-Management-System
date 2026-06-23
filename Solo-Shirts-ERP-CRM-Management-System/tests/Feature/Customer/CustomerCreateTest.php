<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Front Desk');
});

it('creates a customer with a branch-prefixed code, encrypted phone and branch_id', function () {
    $response = $this->withHeaders(bearer($this->user))
        ->postJson('/api/v1/customers', [
            'name' => 'Ramesh Kumar',
            'phone' => '9876543210',
            'address' => 'MG Road',
        ])
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Ramesh Kumar',
                'customer_code' => 'SSI-HQ-000001',
                'phone_masked' => '******3210',
            ],
        ]);

    // Desk-facing roles (Front Desk here) receive the full, decrypted phone so
    // they can contact the customer; the masked form is always present too.
    expect($response->json('data.phone'))->toBe('9876543210');

    $customer = Customer::query()->firstOrFail();
    expect($customer->branch_id)->toBe($this->branch->id)
        ->and($customer->phone)->toBe('9876543210')
        ->and($customer->phone_last4)->toBe('3210');
});

it('rejects a duplicate phone in the same branch with 409 DUPLICATE_PHONE', function () {
    $this->withHeaders(bearer($this->user))
        ->postJson('/api/v1/customers', ['name' => 'A', 'phone' => '9876543210'])
        ->assertCreated();

    $existing = Customer::query()->firstOrFail();

    $this->withHeaders(bearer($this->user))
        ->postJson('/api/v1/customers', ['name' => 'B', 'phone' => '9876543210'])
        ->assertStatus(409)
        ->assertJson([
            'code' => 'DUPLICATE_PHONE',
            'errors' => ['existing_customer_id' => [(string) $existing->id]],
        ]);
});
