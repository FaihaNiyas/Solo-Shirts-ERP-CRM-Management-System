<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Database\Factories\InvoiceFactory;
use App\Modules\Order\Models\Order;
use App\Modules\Shared\Traits\AuditsChanges;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $invoice_no
 * @property int $order_id
 * @property int $customer_id
 * @property string $gst_treatment
 * @property int $subtotal_paise
 * @property int $cgst_paise
 * @property int $sgst_paise
 * @property int $igst_paise
 * @property int $delivery_charges_paise
 * @property int $discount_paise
 * @property int $total_paise
 * @property Carbon $issued_at
 * @property int|null $issued_by
 * @property string $status
 * @property string|null $pdf_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, InvoiceLine> $lines
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, CreditNote> $creditNotes
 */
final class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use AuditsChanges, BelongsToBranch, HasFactory;

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_CREDITED = 'credited';

    public const TREATMENT_REGULAR = 'regular';

    public const TREATMENT_COMPOSITION = 'composition';

    public const TREATMENT_UNREGISTERED = 'unregistered';

    /**
     * @var list<string>
     */
    public const TREATMENTS = [
        self::TREATMENT_REGULAR,
        self::TREATMENT_COMPOSITION,
        self::TREATMENT_UNREGISTERED,
    ];

    protected $fillable = [
        'branch_id',
        'invoice_no',
        'order_id',
        'customer_id',
        'gst_treatment',
        'subtotal_paise',
        'cgst_paise',
        'sgst_paise',
        'igst_paise',
        'delivery_charges_paise',
        'discount_paise',
        'total_paise',
        'issued_at',
        'issued_by',
        'status',
        'pdf_path',
    ];

    protected $casts = [
        'subtotal_paise' => 'integer',
        'cgst_paise' => 'integer',
        'sgst_paise' => 'integer',
        'igst_paise' => 'integer',
        'delivery_charges_paise' => 'integer',
        'discount_paise' => 'integer',
        'total_paise' => 'integer',
        'issued_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    protected function auditAttributes(): array
    {
        return ['invoice_no', 'status', 'total_paise'];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<CreditNote, $this>
     */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }
}
