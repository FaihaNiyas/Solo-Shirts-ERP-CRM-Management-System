<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Database\Factories\FabricRollFactory;
use App\Modules\Shared\Traits\AuditsChanges;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $roll_code
 * @property int $fabric_type_id
 * @property string|null $colour
 * @property int|null $supplier_id
 * @property string $received_length_metres
 * @property string $remaining_metres
 * @property string|null $low_stock_threshold_metres
 * @property int|null $unit_price_paise
 * @property Carbon|null $received_date
 * @property string|null $rack_location
 * @property string $status
 * @property Carbon|null $created_at
 */
final class FabricRoll extends Model
{
    /** @use HasFactory<FabricRollFactory> */
    use AuditsChanges, BelongsToBranch, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DEPLETED = 'depleted';

    public const STATUS_WRITTEN_OFF = 'written_off';

    protected $fillable = [
        'branch_id',
        'roll_code',
        'fabric_type_id',
        'colour',
        'supplier_id',
        'received_length_metres',
        'remaining_metres',
        'low_stock_threshold_metres',
        'unit_price_paise',
        'received_date',
        'rack_location',
        'status',
    ];

    protected $casts = [
        'received_length_metres' => 'decimal:2',
        'remaining_metres' => 'decimal:2',
        'low_stock_threshold_metres' => 'decimal:2',
        'unit_price_paise' => 'integer',
        'received_date' => 'date',
    ];

    /**
     * Per-roll low stock: remaining has fallen below the roll's own reorder
     * threshold. Null threshold ⇒ never flagged at the roll level (the per-type
     * aggregate alert still applies separately).
     */
    public function isLowStock(): bool
    {
        return $this->low_stock_threshold_metres !== null
            && (float) $this->remaining_metres < (float) $this->low_stock_threshold_metres;
    }

    /**
     * @return list<string>
     */
    protected function auditAttributes(): array
    {
        return ['roll_code', 'remaining_metres', 'status'];
    }

    public function isReservable(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return BelongsTo<FabricType, $this>
     */
    public function fabricType(): BelongsTo
    {
        return $this->belongsTo(FabricType::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    protected static function newFactory(): FabricRollFactory
    {
        return FabricRollFactory::new();
    }
}
