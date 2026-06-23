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

    // Profile v1: chest 40 (auto-approved).
    $create = $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/customers/{$this->customer->id}/measurements", [
            'name' => 'Daily fit',
            'type' => 'shirt',
            'shirt_data' => ['chest' => 40, 'waist' => 34, 'shirt_length' => 29],
        ])->assertCreated();

    $this->profileId = $create->json('data.id');
    forgetAuth();
});

it('flags a significant change as pending_approval and raises an alert', function () {
    $response = $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/measurements/profiles/{$this->profileId}/versions", [
            'shirt_data' => ['chest' => 43, 'waist' => 34, 'shirt_length' => 29],
        ])
        ->assertCreated()
        ->assertJson(['data' => [
            'version_number' => 2,
            'status' => 'pending_approval',
            'significant_change' => true,
        ]]);

    $versionId = $response->json('data.id');
    $this->assertDatabaseHas('measurement_alerts', ['version_id' => $versionId]);
});

it('auto-approves a change below the threshold', function () {
    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/measurements/profiles/{$this->profileId}/versions", [
            'shirt_data' => ['chest' => 40.5, 'waist' => 34, 'shirt_length' => 29],
        ])
        ->assertCreated()
        ->assertJson(['data' => [
            'version_number' => 2,
            'status' => 'approved',
            'significant_change' => false,
        ]]);
});

it('produces sequential gap-free version numbers', function () {
    foreach ([40.1, 40.2, 40.3] as $chest) {
        $this->withHeaders(bearer($this->staff))
            ->postJson("/api/v1/measurements/profiles/{$this->profileId}/versions", [
                'shirt_data' => ['chest' => $chest, 'waist' => 34, 'shirt_length' => 29],
            ])->assertCreated();
        forgetAuth();
    }

    $numbers = MeasurementVersion::query()->orderBy('version_number')->pluck('version_number')->all();
    expect($numbers)->toBe([1, 2, 3, 4]);
});

it('approving v2 closes the prior version effective_to window', function () {
    $v2 = $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/measurements/profiles/{$this->profileId}/versions", [
            'shirt_data' => ['chest' => 44, 'waist' => 34, 'shirt_length' => 29],
        ])->assertCreated()->json('data');
    forgetAuth();

    expect($v2['status'])->toBe('pending_approval');

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/measurements/versions/{$v2['id']}/approve")
        ->assertOk()
        ->assertJson(['data' => ['status' => 'approved']]);

    $v1 = MeasurementVersion::query()->where('version_number', 1)->firstOrFail();
    $v2Model = MeasurementVersion::query()->where('version_number', 2)->firstOrFail();

    expect($v1->effective_to)->not->toBeNull()
        ->and($v1->effective_to->equalTo($v2Model->effective_from))->toBeTrue();
});

it('rejects an approval on an already-approved version with 409 ALREADY_APPROVED', function () {
    // v1 is already approved.
    $v1 = MeasurementVersion::query()->where('version_number', 1)->firstOrFail();

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/measurements/versions/{$v1->id}/approve")
        ->assertStatus(409)
        ->assertJson(['code' => 'ALREADY_APPROVED']);
});
