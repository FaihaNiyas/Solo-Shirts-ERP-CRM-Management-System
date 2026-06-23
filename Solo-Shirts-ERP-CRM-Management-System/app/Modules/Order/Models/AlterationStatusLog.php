<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One immutable entry in a customer alteration's status history (Phase 5B).
 *
 * @property int $id
 * @property int $alteration_request_id
 * @property string $previous_status
 * @property string $new_status
 * @property int|null $changed_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 */
final class AlterationStatusLog extends Model
{
    /** Append-only: only the creation time is tracked. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'alteration_request_id',
        'previous_status',
        'new_status',
        'changed_by',
        'notes',
    ];

    /**
     * @return BelongsTo<AlterationRequest, $this>
     */
    public function alteration(): BelongsTo
    {
        return $this->belongsTo(AlterationRequest::class, 'alteration_request_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
