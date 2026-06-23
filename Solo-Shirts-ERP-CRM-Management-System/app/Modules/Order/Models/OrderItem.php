<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Database\Factories\OrderItemFactory;
use App\Modules\Production\Models\ProductionIssue;
use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Production\States\ProductionState;
use App\Modules\Shared\Traits\AuditsChanges;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\HasStates;

/**
 * @property int $id
 * @property int $order_id
 * @property int $branch_id
 * @property string $item_code
 * @property string $product_type
 * @property int $quantity
 * @property int $measurement_version_id
 * @property string|null $fabric_preference_text
 * @property array<string, mixed>|null $design_notes
 * @property ProductionState $state
 * @property Carbon|null $cancelled_at
 * @property string|null $cancel_reason
 * @property Carbon|null $on_hold_at
 * @property string|null $on_hold_reason
 * @property string|null $delivery_box_code
 */
final class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use AuditsChanges, BelongsToBranch, HasFactory, HasStates;

    public const STATE_DRAFT = 'draft';

    public const STATE_FABRIC_ALLOCATED = 'fabric_allocated';

    public const STATE_CUTTING = 'cutting';

    public const STATE_TAILORING = 'tailoring';

    public const STATE_KAJA_BUTTON = 'kaja_button';

    public const STATE_FINISHING = 'finishing';

    public const STATE_QC = 'qc';

    public const STATE_REWORK = 'rework';

    public const STATE_PACKING = 'packing';

    public const STATE_READY_FOR_DELIVERY = 'ready_for_delivery';

    public const STATE_DELIVERED = 'delivered';

    public const STATE_CANCELLED = 'cancelled';

    /**
     * Header/item edits are only allowed before fabric is committed.
     *
     * @var list<string>
     */
    public const EDITABLE_STATES = [self::STATE_DRAFT];

    /**
     * An item may be cancelled freely until it enters cutting.
     *
     * @var list<string>
     */
    public const CANCELLABLE_STATES = [self::STATE_DRAFT, self::STATE_FABRIC_ALLOCATED];

    /**
     * @var list<string>
     */
    public const IN_PRODUCTION_STATES = [
        self::STATE_CUTTING, self::STATE_TAILORING, self::STATE_KAJA_BUTTON,
        self::STATE_FINISHING, self::STATE_QC, self::STATE_REWORK, self::STATE_PACKING,
    ];

    /**
     * The full workflow ordering, used for kanban columns and enumeration.
     *
     * @var list<string>
     */
    public const WORKFLOW_STATES = [
        self::STATE_DRAFT, self::STATE_FABRIC_ALLOCATED, self::STATE_CUTTING,
        self::STATE_TAILORING, self::STATE_KAJA_BUTTON, self::STATE_FINISHING,
        self::STATE_QC, self::STATE_REWORK, self::STATE_PACKING,
        self::STATE_READY_FOR_DELIVERY, self::STATE_DELIVERED, self::STATE_CANCELLED,
    ];

    protected $fillable = [
        'order_id',
        'branch_id',
        'item_code',
        'product_type',
        'quantity',
        'measurement_version_id',
        'fabric_preference_text',
        'design_notes',
        'state',
        'cancelled_at',
        'cancel_reason',
        // Kanban — "On Hold" overlay (parallel to the state machine).
        'on_hold_at',
        'on_hold_reason',
        // Delivery pickup box / shelf — entered at the final move, searched at the
        // Front Desk for collection. Free-text; distinct from the production box.
        'delivery_box_code',
        // Phase 2 — Front Desk production box & placement.
        'production_box_id',
        'box_code',
        'placed_in_box',
        'placed_in_box_at',
        'placed_in_box_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'design_notes' => 'array',
        'cancelled_at' => 'datetime',
        'on_hold_at' => 'datetime',
        'state' => ProductionState::class,
        'placed_in_box' => 'boolean',
        'placed_in_box_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    protected function auditAttributes(): array
    {
        // State changes are audited separately in production_transitions; the
        // State cast must not be logged (it serialises to an object).
        return ['item_code', 'product_type', 'quantity'];
    }

    public function isEditable(): bool
    {
        return in_array((string) $this->state, self::EDITABLE_STATES, true);
    }

    public function isCancellable(): bool
    {
        return in_array((string) $this->state, self::CANCELLABLE_STATES, true);
    }

    public function isOnHold(): bool
    {
        return $this->on_hold_at !== null;
    }

    /**
     * The append-only production transition ledger for this item. Used for board
     * aggregates (rework count, last-transition timestamp).
     *
     * @return HasMany<ProductionTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(ProductionTransition::class, 'order_item_id');
    }

    /**
     * Production issues raised against this item (Kanban). Open issues drive the
     * board's issue-count badge.
     *
     * @return HasMany<ProductionIssue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(ProductionIssue::class, 'order_item_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<MeasurementVersion, $this>
     */
    public function measurementVersion(): BelongsTo
    {
        return $this->belongsTo(MeasurementVersion::class, 'measurement_version_id');
    }

    /**
     * @return BelongsTo<ProductionBox, $this>
     */
    public function productionBox(): BelongsTo
    {
        return $this->belongsTo(ProductionBox::class, 'production_box_id');
    }

    protected static function newFactory(): OrderItemFactory
    {
        return OrderItemFactory::new();
    }
}
