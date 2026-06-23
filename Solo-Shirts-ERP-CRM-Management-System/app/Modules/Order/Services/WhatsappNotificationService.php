<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\WhatsappNotification;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * WhatsApp send via the Meta WhatsApp Cloud API.
 *
 * With no provider configured (services.whatsapp.token / phone_number_id empty)
 * the message is logged as `simulated` — we never claim a real message was sent.
 * When configured, the gateway call is made synchronously and the log is updated
 * to `sent` (+ provider_message_id) or `failed` (+ error_message). A provider
 * error never throws — it is recorded on the log so the desk sees what happened.
 *
 * NOTE: Meta only allows free-form text inside the 24h customer-service window.
 * Business-initiated messages outside that window require a pre-approved template
 * (configure one in Meta Business Manager); such sends will come back `failed`
 * with Meta's reason until template support is added.
 */
final class WhatsappNotificationService
{
    private const GRAPH_BASE = 'https://graph.facebook.com';

    public function send(Order $order, string $eventType, ?int $orderItemId, string $messageBody, User $actor): WhatsappNotification
    {
        $order->loadMissing('customer');

        $phone = trim((string) ($order->customer?->phone ?? ''));
        if ($phone === '' || strlen((string) preg_replace('/\D/', '', $phone)) < 8) {
            throw OrderException::missingPhoneForNotification();
        }

        if (!$this->providerConfigured()) {
            return $this->createLog($order, $eventType, $orderItemId, $phone, $messageBody, $actor, WhatsappNotification::STATUS_SIMULATED);
        }

        $log = $this->createLog($order, $eventType, $orderItemId, $phone, $messageBody, $actor, WhatsappNotification::STATUS_QUEUED);

        $this->deliver($log, $phone, $messageBody);

        return $log->refresh();
    }

    public function providerConfigured(): bool
    {
        return filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
    }

    private function deliver(WhatsappNotification $log, string $phone, string $messageBody): void
    {
        try {
            $version = (string) config('services.whatsapp.api_version', 'v21.0');
            $phoneId = (string) config('services.whatsapp.phone_number_id');
            $token = (string) config('services.whatsapp.token');

            $response = Http::withToken($token)
                ->timeout(15)
                ->acceptJson()
                ->post(self::GRAPH_BASE . "/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $this->toWhatsAppNumber($phone),
                    'type' => 'text',
                    'text' => ['preview_url' => false, 'body' => $messageBody],
                ]);

            if ($response->successful()) {
                $log->update([
                    'status' => WhatsappNotification::STATUS_SENT,
                    'provider_message_id' => $response->json('messages.0.id'),
                    'sent_at' => now(),
                ]);

                return;
            }

            $log->update([
                'status' => WhatsappNotification::STATUS_FAILED,
                'error_message' => $this->errorFrom($response->json()),
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => WhatsappNotification::STATUS_FAILED,
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);
        }
    }

    /**
     * Normalise to the digits-only international form Meta expects (no leading +).
     *
     *  - "00…"  → drop the international 00 prefix (already international).
     *  - "0…"   → national number with a trunk 0 (e.g. 076…) → swap the 0 for the
     *             configured country code.
     *  - 10 digits, no leading 0 (e.g. India's 98…) → prefix the country code.
     *  - anything longer is assumed already international.
     */
    private function toWhatsAppNumber(string $phone): string
    {
        $digits = (string) preg_replace('/\D/', '', $phone);
        $cc = (string) config('services.whatsapp.default_country_code', '91');

        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            return $cc . substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            return $cc . $digits;
        }

        return $digits;
    }

    /**
     * @param  mixed  $body
     */
    private function errorFrom($body): string
    {
        if (is_array($body) && isset($body['error']['message'])) {
            return substr((string) $body['error']['message'], 0, 500);
        }

        return 'WhatsApp provider rejected the message.';
    }

    private function createLog(Order $order, string $eventType, ?int $orderItemId, string $phone, string $messageBody, User $actor, string $status): WhatsappNotification
    {
        /** @var WhatsappNotification $log */
        $log = WhatsappNotification::query()->create([
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'order_item_id' => $orderItemId,
            'channel' => 'whatsapp',
            'event_type' => $eventType,
            'recipient_phone' => $phone,
            'message_body' => $messageBody,
            'status' => $status,
            'sent_by' => $actor->id,
        ]);

        return $log;
    }
}
