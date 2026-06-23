<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Front Desk');
});

it('generates gap-free, unique, sequential codes for a branch', function () {
    for ($i = 1; $i <= 6; $i++) {
        $this->withHeaders(bearer($this->user))
            ->postJson('/api/v1/customers', ['name' => "C{$i}", 'phone' => '90000000' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)])
            ->assertCreated();
    }

    $codes = Customer::query()->orderBy('id')->pluck('customer_code')->all();

    expect($codes)->toBe([
        'SSI-HQ-000001',
        'SSI-HQ-000002',
        'SSI-HQ-000003',
        'SSI-HQ-000004',
        'SSI-HQ-000005',
        'SSI-HQ-000006',
    ])->and(array_unique($codes))->toHaveCount(6);
});
