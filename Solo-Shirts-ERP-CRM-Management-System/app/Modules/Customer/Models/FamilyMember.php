<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Modules\Customer\Database\Factories\FamilyMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $name
 * @property string|null $relation
 * @property Carbon|null $dob
 * @property string|null $gender
 * @property string|null $notes
 */
final class FamilyMember extends Model
{
    /** @use HasFactory<FamilyMemberFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'name',
        'relation',
        'dob',
        'gender',
        'notes',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function newFactory(): FamilyMemberFactory
    {
        return FamilyMemberFactory::new();
    }
}
