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
    $this->customer = Customer::factory()->for($this->branch)->create();
});

it('creates a profile whose first version is auto-approved (no prior to diff)', function () {
    $response = $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/customers/{$this->customer->id}/measurements", [
            'name' => 'Daily fit',
            'type' => 'shirt',
            'shirt_data' => ['chest' => 40, 'waist' => 34, 'shirt_length' => 29],
        ])
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Daily fit',
                'type' => 'shirt',
                'current_version' => [
                    'version_number' => 1,
                    'status' => 'approved',
                    'significant_change' => false,
                ],
            ],
        ]);

    expect($response->json('data.current_version'))->not->toBeNull();

    $version = MeasurementVersion::query()->firstOrFail();
    expect($version->status)->toBe('approved')
        ->and($version->effective_from)->not->toBeNull()
        ->and($version->branch_id)->toBe($this->branch->id);
});
