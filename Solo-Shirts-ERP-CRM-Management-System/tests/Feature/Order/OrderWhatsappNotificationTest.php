<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\WhatsappNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

function configureWhatsapp(): void
{
    config()->set('services.whatsapp.token', 'TEST_TOKEN');
    config()->set('services.whatsapp.phone_number_id', '100000000000000');
    config()->set('services.whatsapp.api_version', 'v21.0');
    config()->set('services.whatsapp.default_country_code', '91');
}

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
});

/**
 * A confirmed order whose customer has a phone.
 *
 * @return array{0:Order,1:Customer}
 */
function notifyOrder($ctx): array
{
    $customer = Customer::factory()->for($ctx->branch)->create(['phone_last4' => '3210']);
    $customer->forceFill(['phone' => '9876543210'])->save();
    $order = Order::factory()->for($ctx->branch)->for($customer)->create(['lifecycle_status' => 'order_received']);

    return [$order, $customer];
}

it('lets Front Desk send an order_confirmed WhatsApp, logged as simulated', function () {
    [$order] = notifyOrder($this);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$order->id}/notifications/whatsapp", ['event_type' => 'order_confirmed'])
        ->assertCreated()
        ->assertJsonPath('data.event_type', 'order_confirmed')
        ->assertJsonPath('data.status', 'simulated');

    $log = WhatsappNotification::query()->first();
    expect($log->channel)->toBe('whatsapp')
        ->and($log->event_type)->toBe('order_confirmed')
        ->and($log->status)->toBe('simulated')
        ->and($log->order_id)->toBe($order->id)
        ->and($log->message_body)->toContain($order->order_code);
});

it('rejects an unsupported event type (422)', function () {
    [$order] = notifyOrder($this);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$order->id}/notifications/whatsapp", ['event_type' => 'marketing_blast'])
        ->assertStatus(422);
});

it('blocks send when the customer has no phone (422 MISSING_PHONE)', function () {
    [$order, $customer] = notifyOrder($this);
    $customer->forceFill(['phone' => ''])->save();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$order->id}/notifications/whatsapp", ['event_type' => 'order_confirmed'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'MISSING_PHONE');

    expect(WhatsappNotification::query()->count())->toBe(0);
});

it('uses an edited message body when provided', function () {
    [$order] = notifyOrder($this);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$order->id}/notifications/whatsapp", [
            'event_type' => 'payment_balance_reminder',
            'message_body' => 'Custom reminder text',
        ])
        ->assertCreated()
        ->assertJsonPath('data.message_body', 'Custom reminder text');
});

it('returns notification history with a masked phone', function () {
    [$order] = notifyOrder($this);
    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$order->id}/notifications/whatsapp", ['event_type' => 'order_confirmed'])->assertCreated();

    $res = $this->withHeaders(bearer($this->fd))
        ->getJson("/api/v1/orders/{$order->id}/notifications")->assertOk();

    expect($res->json('data.0.recipient_phone'))->toBe('****3210')
        ->and($res->json('data.0.event_type'))->toBe('order_confirmed')
        ->and($res->json('data.0.status'))->toBe('simulated');
});

it('generates a message preview without sending', function () {
    [$order] = notifyOrder($this);

    $this->withHeaders(bearer($this->fd))
        ->getJson("/api/v1/orders/{$order->id}/notifications/preview?event_type=order_confirmed")
        ->assertOk()
        ->assertJsonPath('data.provider_configured', false)
        ->assertJsonPath('data.has_phone', true);

    expect(WhatsappNotification::query()->count())->toBe(0);
});

it('forbids sending without the orders.notifications.send permission (403)', function () {
    [$order] = notifyOrder($this);
    $staff = makeUser($this->branch, 'Measurement Staff');

    $this->withHeaders(bearer($staff))
        ->postJson("/api/v1/orders/{$order->id}/notifications/whatsapp", ['event_type' => 'order_confirmed'])
        ->assertStatus(403);
});

it('sends a real message via the Meta Cloud API when configured', function () {
    configureWhatsapp();
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEST123']]], 200)]);
    [$order] = notifyOrder($this);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$order->id}/notifications/whatsapp", ['event_type' => 'order_confirmed'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'sent');

    $log = WhatsappNotification::query()->first();
    expect($log->status)->toBe('sent')
        ->and($log->provider_message_id)->toBe('wamid.TEST123')
        ->and($log->sent_at)->not->toBeNull();

    // The bare 10-digit number is sent in international form (91 + number).
    Http::assertSent(fn ($req) => str_contains($req->url(), '/100000000000000/messages')
        && $req['to'] === '919876543210'
        && $req['type'] === 'text');
});

it('normalises a leading-zero national number to international form', function () {
    configureWhatsapp();
    config()->set('services.whatsapp.default_country_code', '27');
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

    $customer = Customer::factory()->for($this->branch)->create(['phone_last4' => '0975']);
    $customer->forceFill(['phone' => '0768040975'])->save();
    $order = Order::factory()->for($this->branch)->for($customer)->create(['lifecycle_status' => 'order_received']);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$order->id}/notifications/whatsapp", ['event_type' => 'order_confirmed'])
        ->assertCreated()->assertJsonPath('data.status', 'sent');

    // 0768040975 → strip trunk 0, prepend country code 27 → 27768040975
    Http::assertSent(fn ($req) => $req['to'] === '27768040975');
});

it('records a provider rejection as failed without throwing', function () {
    configureWhatsapp();
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Recipient phone number not in allowed list']], 400)]);
    [$order] = notifyOrder($this);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$order->id}/notifications/whatsapp", ['event_type' => 'order_confirmed'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'failed');

    $log = WhatsappNotification::query()->first();
    expect($log->status)->toBe('failed')
        ->and($log->error_message)->toContain('not in allowed list');
});

it('enforces branch scoping on notifications', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $oc = Customer::factory()->for($other)->create(['phone_last4' => '0000']);
    $oc->forceFill(['phone' => '9000000000'])->save();
    $otherOrder = Order::factory()->for($other)->for($oc)->create(['lifecycle_status' => 'order_received']);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$otherOrder->id}/notifications/whatsapp", ['event_type' => 'order_confirmed'])
        ->assertStatus(404);
});
