<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Database\Factories\DamageReportPhotoFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $damage_report_id
 * @property int $branch_id
 * @property string $disk
 * @property string $path
 * @property string|null $thumb_path
 * @property int $size_bytes
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 */
final class DamageReportPhoto extends Model
{
    /** @use HasFactory<DamageReportPhotoFactory> */
    use BelongsToBranch, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'damage_report_id',
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
     * @return BelongsTo<DamageReport, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class, 'damage_report_id');
    }

    protected static function newFactory(): DamageReportPhotoFactory
    {
        return DamageReportPhotoFactory::new();
    }
}
