<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Finance\Database\Factories\CreditNoteFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $credit_no
 * @property int $invoice_id
 * @property string $reason
 * @property int $total_paise
 * @property Carbon $issued_at
 * @property int|null $issued_by
 * @property Carbon|null $created_at
 */
final class CreditNote extends Model
{
    /** @use HasFactory<CreditNoteFactory> */
    use BelongsToBranch, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'branch_id',
        'credit_no',
        'invoice_id',
        'reason',
        'total_paise',
        'issued_at',
        'issued_by',
    ];

    protected $casts = [
        'total_paise' => 'integer',
        'issued_at' => 'datetime',
        'created_at' => 'datetime',
    ];

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
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    protected static function newFactory(): CreditNoteFactory
    {
        return CreditNoteFactory::new();
    }
}
