<?php

declare(strict_types=1);

use App\Modules\Reporting\Models\NotificationMessage;
use App\Modules\Reporting\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    RateLimiter::clear('notifications:whatsapp');
    config()->set('notifications.whatsapp_per_minute', 1);
});

it('keeps a rate-limited WhatsApp message queued for retry rather than losing it', function () {
    $service = app(NotificationService::class);

    // First send is within quota → delivered.
    $first = $service->send(
        NotificationMessage::CHANNEL_WHATSAPP,
        '+919000000001',
        ['template' => 'delivered'],
        $this->branch->id,
        'OrderA',
        1,
    );

    // Second send exceeds the 1/min quota → must stay queued (not failed, not lost).
    $second = $service->send(
        NotificationMessage::CHANNEL_WHATSAPP,
        '+919000000002',
        ['template' => 'delivered'],
        $this->branch->id,
        'OrderB',
        2,
    );

    expect($first->status)->toBe(NotificationMessage::STATUS_SENT)
        ->and($second->status)->toBe(NotificationMessage::STATUS_QUEUED)
        ->and($second->attempt_count)->toBe(1);

    // The over-limit message is still persisted, awaiting retry.
    $this->assertDatabaseHas('notifications', [
        'id' => $second->id,
        'status' => NotificationMessage::STATUS_QUEUED,
    ]);
});
