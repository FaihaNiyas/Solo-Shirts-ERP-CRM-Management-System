<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Models;

use App\Modules\Customer\Models\Customer;
use App\Modules\Measurement\Database\Factories\MeasurementProfileFactory;
use App\Modules\Shared\Traits\BelongsToBranchUnscoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $customer_id
 * @property int|null $family_member_id
 * @property string $name
 * @property string $type
 * @property bool $is_default
 */
final class MeasurementProfile extends Model
{
    /** @use HasFactory<MeasurementProfileFactory> */
    use BelongsToBranchUnscoped, HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'customer_id',
        'family_member_id',
        'name',
        'type',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<MeasurementVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(MeasurementVersion::class, 'profile_id')->orderBy('version_number');
    }

    /**
     * The approved version currently in effect (highest version_number with a
     * null effective_to).
     *
     * @return HasOne<MeasurementVersion, $this>
     */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(MeasurementVersion::class, 'profile_id')->ofMany(
            ['version_number' => 'max'],
            function ($query): void {
                $query->where('status', MeasurementVersion::STATUS_APPROVED)->whereNull('effective_to');
            },
        );
    }

    protected static function newFactory(): MeasurementProfileFactory
    {
        return MeasurementProfileFactory::new();
    }
}
