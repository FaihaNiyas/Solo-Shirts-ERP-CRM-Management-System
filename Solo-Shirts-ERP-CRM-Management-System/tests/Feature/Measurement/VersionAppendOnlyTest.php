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

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/customers/{$this->customer->id}/measurements", [
            'name' => 'Daily fit',
            'type' => 'shirt',
            'shirt_data' => ['chest' => 40, 'waist' => 34, 'shirt_length' => 29],
        ])->assertCreated();
    forgetAuth();
});

it('forbids mutating the measurement data of a persisted version', function () {
    $version = MeasurementVersion::query()->firstOrFail();

    $version->shirt_data = ['chest' => 99];

    expect(fn () => $version->save())->toThrow(RuntimeException::class);
});

it('allows updating approval lifecycle fields (status/effective_to) without error', function () {
    $version = MeasurementVersion::query()->firstOrFail();

    $version->effective_to = now();

    expect(fn () => $version->save())->not->toThrow(RuntimeException::class);
});
