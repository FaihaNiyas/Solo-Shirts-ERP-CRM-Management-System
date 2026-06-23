<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('passes the restore drill when core invariants hold', function () {
    Customer::factory()->for($this->branch)->create();

    $this->artisan('backup:verify')
        ->expectsOutputToContain('Backup verification passed')
        ->assertExitCode(0);
});

it('fails the restore drill when the database looks empty or incomplete', function () {
    // No customers seeded → an incomplete/empty restore must be flagged.
    $this->artisan('backup:verify')
        ->expectsOutputToContain('no customers present')
        ->assertExitCode(1);
});
