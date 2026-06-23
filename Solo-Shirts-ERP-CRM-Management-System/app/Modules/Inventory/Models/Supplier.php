<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Database\Factories\SupplierFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $code
 * @property string $name
 * @property string|null $gstin
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $payment_terms
 * @property bool $is_active
 */
final class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use BelongsToBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'gstin',
        'phone',
        'email',
        'address',
        'payment_terms',
        'is_active',
    ];

    protected $casts = [
        'phone' => 'encrypted',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }
}
