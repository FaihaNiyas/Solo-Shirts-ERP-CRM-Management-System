<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Http\Requests\SendWhatsappRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\WhatsappNotification;
use App\Modules\Order\Services\WhatsappMessageBuilder;
use App\Modules\Order\Services\WhatsappNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Front Desk WhatsApp triggers + history for an order. Read-only preview, a send
 * that always logs (and is honest about `simulated` vs `sent`), and a masked
 * history feed. No bulk/marketing capability.
 */
final class OrderNotificationController extends BaseApiController
{
    public function __construct(
        private readonly WhatsappMessageBuilder $builder,
        private readonly WhatsappNotificationService $sender,
    ) {}

    /** Generated message preview for an event (so the modal can show/edit it). */
    public function preview(Request $request, Order $order): JsonResponse
    {
        $this->authorize('viewNotifications', Order::class);

        $eventType = (string) $request->query('event_type', WhatsappNotification::EVENT_ORDER_CONFIRMED);
        if (!in_array($eventType, WhatsappNotification::EVENTS, true)) {
            $eventType = WhatsappNotification::EVENT_ORDER_CONFIRMED;
        }

        $order->loadMissing('customer');
        $phone = trim((string) ($order->customer?->phone ?? ''));

        return $this->respond([
            'event_type' => $eventType,
            'recipient_phone' => $this->mask($phone),
            'has_phone' => $phone !== '',
            'provider_configured' => $this->sender->providerConfigured(),
            'message_body' => $this->builder->build($order, $eventType),
        ]);
    }

    public function store(SendWhatsappRequest $request, Order $order): JsonResponse
    {
        $this->authorize('sendNotification', Order::class);

        /** @var User $actor */
        $actor = $request->user();
        $eventType = (string) $request->input('event_type');
        $body = $request->filled('message_body')
            ? (string) $request->input('message_body')
            : $this->builder->build($order, $eventType);

        $log = $this->sender->send(
            $order,
            $eventType,
            $request->input('order_item_id') !== null ? (int) $request->input('order_item_id') : null,
            $body,
            $actor,
        );

        return $this->respond([
            'notification_id' => $log->id,
            'event_type' => $log->event_type,
            'recipient_phone' => $this->mask($log->recipient_phone),
            'status' => $log->status,
            'message_body' => $log->message_body,
        ], $this->statusMessage($log->status), 201);
    }

    public function index(Order $order): JsonResponse
    {
        $this->authorize('viewNotifications', Order::class);

        $logs = WhatsappNotification::query()
            ->where('order_id', $order->id)
            ->with('sender:id,name')
            ->latest('id')
            ->get();

        return $this->respond($logs->map(fn (WhatsappNotification $n): array => [
            'id' => $n->id,
            'event_type' => $n->event_type,
            'channel' => $n->channel,
            'recipient_phone' => $this->mask($n->recipient_phone),
            'status' => $n->status,
            'sent_by' => $n->sender?->name,
            'created_at' => $n->created_at?->toIso8601String(),
            'sent_at' => $n->sent_at?->toIso8601String(),
            'preview' => Str::limit($n->message_body, 90),
            'error' => $n->error_message,
        ])->all());
    }

    private function mask(string $phone): ?string
    {
        $digits = (string) preg_replace('/\D/', '', $phone);

        return $digits === '' ? null : '****' . substr($digits, -4);
    }

    private function statusMessage(string $status): string
    {
        return match ($status) {
            WhatsappNotification::STATUS_SENT => 'WhatsApp message sent',
            WhatsappNotification::STATUS_QUEUED => 'WhatsApp message queued',
            WhatsappNotification::STATUS_SIMULATED => 'WhatsApp simulated — provider not configured',
            default => 'WhatsApp send failed',
        };
    }
}
