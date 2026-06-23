<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Printing\Models\Document;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One row per job-card print / reprint of a sub-order.
 *
 * @property int $id
 * @property int $branch_id
 * @property int|null $document_id
 * @property int $order_item_id
 * @property int|null $printed_by
 * @property Carbon $printed_at
 * @property bool $is_reprint
 * @property string|null $reason
 */
final class PrintLog extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'document_id',
        'order_item_id',
        'printed_by',
        'printed_at',
        'is_reprint',
        'reason',
    ];

    protected $casts = [
        'document_id' => 'integer',
        'order_item_id' => 'integer',
        'printed_at' => 'datetime',
        'is_reprint' => 'boolean',
    ];

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function printer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
