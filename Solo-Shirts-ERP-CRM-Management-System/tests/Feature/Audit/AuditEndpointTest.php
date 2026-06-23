<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->admin = makeUser($this->branch, 'Admin');
});

it('lists audit activities filtered by subject type for Owner/Admin', function () {
    Customer::factory()->for($this->branch)->create();

    $this->withHeaders(bearer($this->admin))
        ->getJson('/api/v1/audit/activities?subject_type=' . urlencode(Customer::class))
        ->assertOk()
        ->assertJsonPath('data.0.subject_type', Customer::class);
});

it('returns an item\'s production transition history', function () {
    $item = productionItem($this->branch, 'packing');
    transitionItem($this, $this->admin, $item->id, ['to' => 'ready_for_delivery'])->assertOk();

    $this->withHeaders(bearer($this->admin))
        ->getJson("/api/v1/audit/transitions/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.0.to_state', OrderItem::STATE_READY_FOR_DELIVERY);
});

it('forbids audit access without the permission', function () {
    $tailor = makeUser($this->branch, 'Tailor');

    $this->withHeaders(bearer($tailor))
        ->getJson('/api/v1/audit/activities')
        ->assertForbidden();
});
