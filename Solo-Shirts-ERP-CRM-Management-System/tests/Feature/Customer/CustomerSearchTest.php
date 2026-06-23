<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Front Desk');

    Customer::factory()->for($this->branch)->create(['name' => 'Suresh Patel', 'phone_last4' => '1234']);
    Customer::factory()->for($this->branch)->create(['name' => 'Mahesh Shah', 'phone_last4' => '9999']);
});

it('searches by partial name', function () {
    $response = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/customers?search=resh')
        ->assertOk();

    $names = collect($response->json('data.data'))->pluck('name');
    expect($names)->toContain('Suresh Patel')->not->toContain('Mahesh Shah');
});

it('searches by phone last 4', function () {
    $response = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/customers?search=9999')
        ->assertOk();

    $names = collect($response->json('data.data'))->pluck('name');
    expect($names)->toContain('Mahesh Shah')->not->toContain('Suresh Patel');
});

it('returns the full customer phone to Front Desk in the list', function () {
    $c = Customer::factory()->for($this->branch)->create(['name' => 'Full Phone', 'phone_last4' => '4321']);
    $c->forceFill(['phone' => '9876544321'])->save();

    $response = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/customers?search=Full Phone')->assertOk();

    $row = collect($response->json('data.data'))->firstWhere('name', 'Full Phone');
    expect($row['phone'])->toBe('9876544321')->and($row['phone_last4'])->toBe('4321');
});

it('hides the full phone from a role that is not Front Desk/Admin', function () {
    $staff = makeUser($this->branch, 'Measurement Staff'); // holds customers.view, not a full-phone role
    $c = Customer::factory()->for($this->branch)->create(['name' => 'Masked Phone', 'phone_last4' => '5555']);
    $c->forceFill(['phone' => '9000055555'])->save();

    $response = $this->withHeaders(bearer($staff))
        ->getJson('/api/v1/customers?search=Masked Phone')->assertOk();

    $row = collect($response->json('data.data'))->firstWhere('name', 'Masked Phone');
    expect($row['phone'])->toBeNull()->and($row['phone_last4'])->toBe('5555');
});
