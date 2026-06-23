<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Modules\Production\Database\Factories\QcDefectPhotoFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $qc_defect_id
 * @property int $branch_id
 * @property string $disk
 * @property string $path
 * @property string|null $thumb_path
 * @property int $size_bytes
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 */
final class QcDefectPhoto extends Model
{
    /** @use HasFactory<QcDefectPhotoFactory> */
    use BelongsToBranch, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'qc_defect_id',
        'branch_id',
        'disk',
        'path',
        'thumb_path',
        'size_bytes',
        'uploaded_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function storageDisk(): Filesystem
    {
        return Storage::disk($this->disk);
    }

    /**
     * @return BelongsTo<QcDefect, $this>
     */
    public function defect(): BelongsTo
    {
        return $this->belongsTo(QcDefect::class, 'qc_defect_id');
    }

    protected static function newFactory(): QcDefectPhotoFactory
    {
        return QcDefectPhotoFactory::new();
    }
}
