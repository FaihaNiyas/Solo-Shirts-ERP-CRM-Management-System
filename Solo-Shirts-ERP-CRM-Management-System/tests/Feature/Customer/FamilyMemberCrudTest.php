<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Front Desk');
    $this->customer = Customer::factory()->for($this->branch)->create();
});

it('creates, updates and soft-deletes a family member', function () {
    $headers = bearer($this->user);

    $created = $this->withHeaders($headers)
        ->postJson("/api/v1/customers/{$this->customer->id}/family-members", [
            'name' => 'Junior',
            'relation' => 'son',
            'gender' => 'male',
        ])
        ->assertCreated()
        ->assertJson(['data' => ['name' => 'Junior']]);

    $fid = $created->json('data.id');

    $this->withHeaders($headers)
        ->putJson("/api/v1/customers/{$this->customer->id}/family-members/{$fid}", ['name' => 'Junior R'])
        ->assertOk()
        ->assertJson(['data' => ['name' => 'Junior R']]);

    $this->withHeaders($headers)
        ->deleteJson("/api/v1/customers/{$this->customer->id}/family-members/{$fid}")
        ->assertOk();

    $this->assertSoftDeleted('family_members', ['id' => $fid]);
});

it('lists a customer family members', function () {
    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/customers/{$this->customer->id}/family-members", ['name' => 'Junior', 'relation' => 'son'])
        ->assertCreated();

    $this->withHeaders(bearer($this->user))
        ->getJson("/api/v1/customers/{$this->customer->id}/family-members")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Junior');
});

it('can attach a family member to a customer registered at another branch (customers are global)', function () {
    $branchB = makeBranch(['code' => 'B']);
    $customerB = Customer::factory()->for($branchB)->create();

    $this->withHeaders(bearer($this->user))
        ->postJson("/api/v1/customers/{$customerB->id}/family-members", ['name' => 'X'])
        ->assertCreated();
});
