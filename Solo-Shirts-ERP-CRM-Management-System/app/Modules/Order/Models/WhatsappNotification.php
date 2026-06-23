<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One outbound Front Desk WhatsApp message against an order.
 *
 * @property int $id
 * @property int $branch_id
 * @property int|null $customer_id
 * @property int|null $order_id
 * @property int|null $order_item_id
 * @property string $channel
 * @property string $event_type
 * @property string $recipient_phone
 * @property string $message_body
 * @property string $status
 * @property string|null $provider_message_id
 * @property string|null $error_message
 * @property int|null $sent_by
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 */
final class WhatsappNotification extends Model
{
    use BelongsToBranch;

    protected $table = 'order_whatsapp_notifications';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SIMULATED = 'simulated';

    public const EVENT_ORDER_CONFIRMED = 'order_confirmed';

    public const EVENT_ORDER_READY = 'order_ready_for_pickup';

    public const EVENT_BALANCE_REMINDER = 'payment_balance_reminder';

    public const EVENT_ORDER_DELIVERED = 'order_delivered';

    public const EVENT_DELIVERY_RESCHEDULED = 'delivery_rescheduled';

    /**
     * @var list<string>
     */
    public const EVENTS = [
        self::EVENT_ORDER_CONFIRMED,
        self::EVENT_ORDER_READY,
        self::EVENT_BALANCE_REMINDER,
        self::EVENT_ORDER_DELIVERED,
        self::EVENT_DELIVERY_RESCHEDULED,
    ];

    protected $fillable = [
        'branch_id',
        'customer_id',
        'order_id',
        'order_item_id',
        'channel',
        'event_type',
        'recipient_phone',
        'message_body',
        'status',
        'provider_message_id',
        'error_message',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
