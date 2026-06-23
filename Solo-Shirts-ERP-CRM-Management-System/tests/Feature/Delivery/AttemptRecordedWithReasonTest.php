<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\DeliveryAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('records a failed attempt with a structured reason code and flips status to attempted', function () {
    $delivery = makeDelivery($this->branch, null, ['status' => Delivery::STATUS_DISPATCHED]);

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/deliveries/{$delivery->id}/attempt", [
            'reason_code' => DeliveryAttempt::REASON_CUSTOMER_UNAVAILABLE,
            'notes' => 'No one answered the door',
        ])
        ->assertCreated()
        ->assertJsonPath('data.reason_code', DeliveryAttempt::REASON_CUSTOMER_UNAVAILABLE);

    $this->assertDatabaseHas('delivery_attempts', [
        'delivery_id' => $delivery->id,
        'reason_code' => DeliveryAttempt::REASON_CUSTOMER_UNAVAILABLE,
        'reason_notes' => 'No one answered the door',
    ]);

    expect($delivery->fresh()->status)->toBe(Delivery::STATUS_ATTEMPTED);
});

it('rejects an unknown reason code with 422', function () {
    $delivery = makeDelivery($this->branch, null, ['status' => Delivery::STATUS_DISPATCHED]);

    $this->withHeaders(bearer($this->staff))
        ->postJson("/api/v1/deliveries/{$delivery->id}/attempt", ['reason_code' => 'aliens'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');
});
