<?php

declare(strict_types=1);

use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Measurement\Rules\UsableMeasurementVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

/**
 * Orders FK to measurement_version_id. There is NO measurement approval gate —
 * any existing version (draft / pending / approved) is usable immediately.
 */
function validatesVersion(int $id): bool
{
    return Validator::make(
        ['measurement_version_id' => $id],
        ['measurement_version_id' => [new UsableMeasurementVersion]],
    )->passes();
}

it('accepts an approved measurement version', function () {
    $approved = MeasurementVersion::factory()->create();

    expect(validatesVersion($approved->id))->toBeTrue();
});

it('accepts a pending (non-approved) measurement version — no approval required', function () {
    $pending = MeasurementVersion::factory()->pending()->create(['version_number' => 2]);

    expect(validatesVersion($pending->id))->toBeTrue();
});

it('rejects a non-existent version', function () {
    expect(validatesVersion(999999))->toBeFalse();
});
