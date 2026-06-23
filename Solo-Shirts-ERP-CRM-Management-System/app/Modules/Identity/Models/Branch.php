<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $gst_number
 * @property string|null $phone
 * @property bool $is_active
 */
final class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'gst_number',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Branch>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Branch>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }
}
