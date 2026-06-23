<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Models;

use App\Modules\Reporting\Database\Factories\NotificationMessageFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One outbound notification. Deduped on (channel, reference_type, reference_id);
 * a rate-limited send stays `queued` for retry rather than being dropped.
 *
 * @property int $id
 * @property int $branch_id
 * @property string $channel
 * @property string $recipient
 * @property array<string, mixed>|null $payload
 * @property string $status
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property int $attempt_count
 * @property Carbon|null $sent_at
 * @property string|null $error
 */
final class NotificationMessage extends Model
{
    /** @use HasFactory<NotificationMessageFactory> */
    use BelongsToBranch, HasFactory;

    protected $table = 'notifications';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'branch_id',
        'channel',
        'recipient',
        'payload',
        'status',
        'reference_type',
        'reference_id',
        'attempt_count',
        'sent_at',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'reference_id' => 'integer',
        'attempt_count' => 'integer',
        'sent_at' => 'datetime',
    ];

    protected static function newFactory(): NotificationMessageFactory
    {
        return NotificationMessageFactory::new();
    }
}
