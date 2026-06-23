<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Finance\Database\Factories\PaymentFactory;
use App\Modules\Shared\Traits\AuditsChanges;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An append-only payment against an invoice. The table is INSERT-only at the DB
 * level; there is no updated_at by design. The UPI ID is encrypted at rest.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $invoice_id
 * @property string $method
 * @property int $amount_paise
 * @property string|null $reference_no
 * @property Carbon $paid_at
 * @property int|null $recorded_by
 * @property string|null $upi_id
 * @property string|null $bank_account_last4
 * @property string $idempotency_key
 * @property Carbon|null $created_at
 */
final class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use AuditsChanges, BelongsToBranch, HasFactory;

    public const UPDATED_AT = null;

    public const METHOD_CASH = 'cash';

    public const METHOD_UPI = 'upi';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    /**
     * @var list<string>
     */
    public const METHODS = [self::METHOD_CASH, self::METHOD_UPI, self::METHOD_BANK_TRANSFER];

    protected $fillable = [
        'branch_id',
        'invoice_id',
        'method',
        'amount_paise',
        'reference_no',
        'paid_at',
        'recorded_by',
        'upi_id',
        'bank_account_last4',
        'idempotency_key',
    ];

    protected $hidden = [
        'upi_id',
    ];

    protected $casts = [
        'amount_paise' => 'integer',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'upi_id' => 'encrypted',
    ];

    /**
     * @return list<string>
     */
    protected function auditAttributes(): array
    {
        // Never the encrypted upi_id.
        return ['method', 'amount_paise', 'invoice_id'];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
