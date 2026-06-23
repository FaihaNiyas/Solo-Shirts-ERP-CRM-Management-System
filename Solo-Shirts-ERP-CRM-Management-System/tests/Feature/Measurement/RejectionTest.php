<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Measurement\Models\MeasurementVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Measurement Staff');
    $this->supervisor = makeUser($this->branch, 'QC Supervisor');
    $this->customer = Customer::factory()->for($this->branch)->create();

    $create = $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/customers/{$this->customer->id}/measurements", [
            'name' => 'Daily fit',
            'type' => 'shirt',
            'shirt_data' => ['chest' => 40, 'waist' => 34, 'shirt_length' => 29],
        ])->assertCreated();
    $this->profileId = $create->json('data.id');
    forgetAuth();

    // A significant v2 that needs approval.
    $this->v2 = $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/measurements/profiles/{$this->profileId}/versions", [
            'shirt_data' => ['chest' => 45, 'waist' => 34, 'shirt_length' => 29],
        ])->assertCreated()->json('data.id');
    forgetAuth();
});

it('requires a reason to reject', function () {
    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/measurements/versions/{$this->v2}/reject", [])
        ->assertStatus(422)
        ->assertJson(['code' => 'VALIDATION_FAILED']);
});

it('rejecting a version keeps it non-effective and leaves the prior effective', function () {
    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/measurements/versions/{$this->v2}/reject", ['reason' => 'Chest measurement looks wrong'])
        ->assertOk()
        ->assertJson(['data' => ['status' => 'rejected']]);

    $v2 = MeasurementVersion::query()->find($this->v2);
    expect($v2->status)->toBe('rejected')
        ->and($v2->effective_from)->toBeNull();

    $v1 = MeasurementVersion::query()->where('version_number', 1)->firstOrFail();
    expect($v1->effective_to)->toBeNull(); // still the effective version
});

it('forbids rejecting an already-approved version (409)', function () {
    $v1 = MeasurementVersion::query()->where('version_number', 1)->firstOrFail();

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/measurements/versions/{$v1->id}/reject", ['reason' => 'nope'])
        ->assertStatus(409);
});
