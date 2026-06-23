<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Front Desk');
    $this->customer = Customer::factory()->for($this->branch)->create();
});

it('allows an order that references a non-approved measurement version (no approval gate)', function () {
    $profile = MeasurementProfile::factory()->for($this->branch)->for($this->customer)->create();
    $pending = MeasurementVersion::factory()->pending()->for($this->branch)->for($profile, 'profile')->create();

    $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $pending->id))
        ->assertCreated();
});

it('rejects an order that references a measurement version from another branch', function () {
    $branchB = makeBranch(['code' => 'B']);
    $customerB = Customer::factory()->for($branchB)->create();
    $versionB = approvedVersionFor($branchB, $customerB);

    $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $versionB->id))
        ->assertStatus(422)
        ->assertJson(['code' => 'VALIDATION_FAILED']);
});
