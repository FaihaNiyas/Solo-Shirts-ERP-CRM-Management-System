<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branchA = makeBranch(['code' => 'A']);
    $this->branchB = makeBranch(['code' => 'B']);
});

it('lets a branch user read a customer measurement profile/version from another branch (global)', function () {
    $userA = makeUser($this->branchA, 'Measurement Staff');

    $customerB = Customer::factory()->for($this->branchB)->create();
    $profileB = MeasurementProfile::factory()->for($this->branchB)->for($customerB)->create();
    $versionB = MeasurementVersion::factory()
        ->for($this->branchB)
        ->for($profileB, 'profile')
        ->create();

    $this->withHeaders(bearer($userA))
        ->getJson("/api/v1/measurements/profiles/{$profileB->id}/versions")
        ->assertOk();
    forgetAuth();

    $this->withHeaders(bearer($userA))
        ->getJson("/api/v1/measurements/versions/{$versionB->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $versionB->id);
});
