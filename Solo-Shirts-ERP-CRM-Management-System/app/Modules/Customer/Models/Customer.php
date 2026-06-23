<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Modules\Customer\Database\Factories\CustomerFactory;
use App\Modules\Shared\Traits\AuditsChanges;
use App\Modules\Shared\Traits\BelongsToBranchUnscoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $customer_code
 * @property string $name
 * @property string|null $phone
 * @property string|null $phone_last4
 * @property string|null $address
 * @property int|null $preferred_fabric_id
 * @property string|null $special_notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use AuditsChanges, BelongsToBranchUnscoped, HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'customer_code',
        'name',
        'phone',
        'phone_last4',
        'phone_search',
        'address',
        'preferred_fabric_id',
        'special_notes',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'phone',
    ];

    protected $casts = [
        'phone' => 'encrypted',
    ];

    /**
     * @return list<string>
     */
    protected function auditAttributes(): array
    {
        // Never the encrypted phone.
        return ['name', 'customer_code', 'address'];
    }

    /**
     * @return HasMany<FamilyMember, $this>
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
