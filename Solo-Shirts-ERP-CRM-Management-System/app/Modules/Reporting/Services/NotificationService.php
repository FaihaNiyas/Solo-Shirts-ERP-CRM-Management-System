<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Services;

use App\Modules\Reporting\Models\NotificationMessage;
use App\Modules\Reporting\Notifications\EmailChannel;
use App\Modules\Reporting\Notifications\NotificationChannelInterface;
use App\Modules\Reporting\Notifications\NotificationRateLimitedException;
use App\Modules\Reporting\Notifications\SmsChannel;
use App\Modules\Reporting\Notifications\WhatsappChannel;
use Illuminate\Database\UniqueConstraintViolationException;
use RuntimeException;
use Throwable;

/**
 * Persists and dispatches outbound notifications. Deduplicates on (channel,
 * reference, reference_id) so a given business event sends at most once, and a
 * rate-limited send is left `queued` for retry instead of being lost.
 */
final class NotificationService
{
    /**
     * @var array<string, NotificationChannelInterface>
     */
    private array $channels = [];

    public function __construct(WhatsappChannel $whatsapp, EmailChannel $email, SmsChannel $sms)
    {
        foreach ([$whatsapp, $email, $sms] as $channel) {
            $this->channels[$channel->channel()] = $channel;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(
        string $channel,
        string $recipient,
        array $payload,
        int $branchId,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): NotificationMessage {
        if ($referenceType !== null && $referenceId !== null) {
            $existing = $this->findByReference($channel, $referenceType, $referenceId);

            if ($existing !== null) {
                return $existing;
            }
        }

        try {
            $message = NotificationMessage::query()->create([
                'branch_id' => $branchId,
                'channel' => $channel,
                'recipient' => $recipient,
                'payload' => $payload,
                'status' => NotificationMessage::STATUS_QUEUED,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'attempt_count' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Lost the dedupe race; return the message the winner created.
            return $this->findByReference($channel, (string) $referenceType, (int) $referenceId)
                ?? throw new RuntimeException('Notification dedupe race could not be resolved.');
        }

        $this->attempt($message);

        return $message->refresh();
    }

    /**
     * Attempt delivery once: sent on success, left queued (for retry) when rate
     * limited, failed on any other error. attempt_count always advances.
     */
    public function attempt(NotificationMessage $message): void
    {
        $channel = $this->channels[$message->channel]
            ?? throw new RuntimeException("Unsupported notification channel [{$message->channel}].");

        $message->increment('attempt_count');

        try {
            $channel->send($message->recipient, $message->payload ?? []);
            $message->update([
                'status' => NotificationMessage::STATUS_SENT,
                'sent_at' => now(),
                'error' => null,
            ]);
        } catch (NotificationRateLimitedException $e) {
            // Not lost — stays queued for a later retry.
            $message->update([
                'status' => NotificationMessage::STATUS_QUEUED,
                'error' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            $message->update([
                'status' => NotificationMessage::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function findByReference(string $channel, string $referenceType, int $referenceId): ?NotificationMessage
    {
        return NotificationMessage::query()->withoutGlobalScopes()
            ->where('channel', $channel)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->first();
    }
}
