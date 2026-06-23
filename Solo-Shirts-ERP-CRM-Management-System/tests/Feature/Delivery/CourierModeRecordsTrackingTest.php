<?php

declare(strict_types=1);

use App\Modules\Delivery\Models\Delivery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Delivery Staff');
});

it('creates a courier delivery and persists the partner and tracking number', function () {
    $order = deliverableOrder($this->branch, 1);

    $this->withHeaders(bearer($this->staff))
        ->postJson('/api/v1/deliveries', [
            'order_id' => $order->id,
            'mode' => Delivery::MODE_COURIER,
            'courier_partner' => 'BlueDart',
            'tracking_no' => 'BD123456789',
        ])
        ->assertCreated()
        ->assertJsonPath('data.mode', Delivery::MODE_COURIER)
        ->assertJsonPath('data.tracking_no', 'BD123456789');

    $this->assertDatabaseHas('deliveries', [
        'order_id' => $order->id,
        'mode' => Delivery::MODE_COURIER,
        'courier_partner' => 'BlueDart',
        'tracking_no' => 'BD123456789',
    ]);
});

it('requires a courier partner when mode is courier', function () {
    $order = deliverableOrder($this->branch, 1);

    $this->withHeaders(bearer($this->staff))
        ->postJson('/api/v1/deliveries', [
            'order_id' => $order->id,
            'mode' => Delivery::MODE_COURIER,
        ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');
});
