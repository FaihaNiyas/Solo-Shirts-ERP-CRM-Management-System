<?php

declare(strict_types=1);

namespace App\Modules\Printing\Models;

use App\Models\User;
use App\Modules\Printing\Database\Factories\DocumentFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A rendered, content-addressed PDF. The raw file lives on `disk` at `path`;
 * callers only ever receive a temporary signed download URL, never the path.
 *
 * @property int $id
 * @property int $branch_id
 * @property string $kind
 * @property string $reference_type
 * @property int $reference_id
 * @property string $disk
 * @property string $path
 * @property string $content_hash
 * @property int $size_bytes
 * @property int|null $generated_by
 * @property Carbon $generated_at
 * @property Carbon|null $created_at
 */
final class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use BelongsToBranch, HasFactory;

    public const KIND_JOB_CARD = 'job_card';

    public const KIND_MEASUREMENT_CARD = 'measurement_card';

    public const KIND_GST_INVOICE = 'gst_invoice';

    public const KIND_PACKING_SLIP = 'packing_slip';

    public const KIND_DELIVERY_RECEIPT = 'delivery_receipt';

    /** Per-pickup-batch collection slip (Phase 2). Like report, not regen-able. */
    public const KIND_PICKUP_RECEIPT = 'pickup_receipt';

    /**
     * Report exports are filed as documents too, but are not user-renderable via
     * the regenerate endpoint, so this kind is intentionally excluded from KINDS.
     */
    public const KIND_REPORT = 'report';

    /**
     * @var list<string>
     */
    public const KINDS = [
        self::KIND_JOB_CARD,
        self::KIND_MEASUREMENT_CARD,
        self::KIND_GST_INVOICE,
        self::KIND_PACKING_SLIP,
        self::KIND_DELIVERY_RECEIPT,
    ];

    protected $fillable = [
        'branch_id',
        'kind',
        'reference_type',
        'reference_id',
        'disk',
        'path',
        'content_hash',
        'size_bytes',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'reference_id' => 'integer',
        'size_bytes' => 'integer',
        'generated_at' => 'datetime',
    ];

    public function storageDisk(): Filesystem
    {
        return Storage::disk($this->disk);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }
}
