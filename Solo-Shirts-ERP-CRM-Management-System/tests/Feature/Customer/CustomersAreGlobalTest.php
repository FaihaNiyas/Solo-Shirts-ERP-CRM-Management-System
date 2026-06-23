<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branchA = makeBranch(['code' => 'A']);
    $this->branchB = makeBranch(['code' => 'B']);
});

it('lets a branch user open a customer registered at another branch (customers are global)', function () {
    $userA = makeUser($this->branchA, 'Front Desk');
    $customerB = Customer::factory()->for($this->branchB)->create(['name' => 'Global Cust']);

    $this->withHeaders(bearer($userA))
        ->getJson("/api/v1/customers/{$customerB->id}")
        ->assertOk()
        ->assertJson(['data' => ['name' => 'Global Cust']]);
});

it('lists customers across all branches', function () {
    $userA = makeUser($this->branchA, 'Front Desk');
    Customer::factory()->for($this->branchA)->create(['name' => 'A One']);
    Customer::factory()->for($this->branchB)->create(['name' => 'B One']);

    $response = $this->withHeaders(bearer($userA))->getJson('/api/v1/customers')->assertOk();

    $names = collect($response->json('data.data'))->pluck('name');
    expect($names)->toContain('A One')->toContain('B One');
});

it('enforces phone uniqueness globally across branches (409 DUPLICATE_PHONE)', function () {
    $userA = makeUser($this->branchA, 'Front Desk');
    $userB = makeUser($this->branchB, 'Front Desk');

    $this->withHeaders(bearer($userA))
        ->postJson('/api/v1/customers', ['name' => 'First', 'phone' => '9876500000'])
        ->assertCreated();

    // Same phone at a DIFFERENT branch is now rejected — customers are shared.
    $this->withHeaders(bearer($userB))
        ->postJson('/api/v1/customers', ['name' => 'Second', 'phone' => '9876500000'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'DUPLICATE_PHONE');
});
