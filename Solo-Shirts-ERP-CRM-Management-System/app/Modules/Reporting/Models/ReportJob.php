<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Models;

use App\Models\User;
use App\Modules\Printing\Models\Document;
use App\Modules\Reporting\Database\Factories\ReportJobFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $kind
 * @property array<string, mixed>|null $params
 * @property string $status
 * @property int|null $document_id
 * @property string|null $error
 * @property int|null $requested_by
 * @property Carbon $requested_at
 * @property Carbon|null $completed_at
 */
final class ReportJob extends Model
{
    /** @use HasFactory<ReportJobFactory> */
    use BelongsToBranch, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'branch_id',
        'kind',
        'params',
        'status',
        'document_id',
        'error',
        'requested_by',
        'requested_at',
        'completed_at',
    ];

    protected $casts = [
        'params' => 'array',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

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
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    protected static function newFactory(): ReportJobFactory
    {
        return ReportJobFactory::new();
    }
}
