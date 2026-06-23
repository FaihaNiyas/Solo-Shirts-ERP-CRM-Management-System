<?php

declare(strict_types=1);

use App\Modules\Order\Models\Order;
use App\Modules\Reporting\Models\NotificationMessage;
use App\Modules\Reporting\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('sends a referenced notification only once under deduplication', function () {
    $service = app(NotificationService::class);

    $first = $service->send(
        NotificationMessage::CHANNEL_EMAIL,
        'customer@example.com',
        ['template' => 'ready_for_delivery'],
        $this->branch->id,
        Order::class,
        42,
    );

    $second = $service->send(
        NotificationMessage::CHANNEL_EMAIL,
        'customer@example.com',
        ['template' => 'ready_for_delivery'],
        $this->branch->id,
        Order::class,
        42,
    );

    expect($second->id)->toBe($first->id)
        ->and($first->status)->toBe(NotificationMessage::STATUS_SENT);

    expect(NotificationMessage::query()->where('reference_type', Order::class)->where('reference_id', 42)->count())
        ->toBe(1);
});
