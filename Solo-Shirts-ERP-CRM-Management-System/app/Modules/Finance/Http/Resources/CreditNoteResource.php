<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Resources;

use App\Modules\Finance\Models\CreditNote;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin CreditNote
 */
final class CreditNoteResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'credit_no' => $this->credit_no,
            'invoice_id' => $this->invoice_id,
            'reason' => $this->reason,
            'total_paise' => $this->total_paise,
            'issued_at' => $this->date($this->issued_at),
            'issued_by' => $this->issued_by,
        ];
    }
}
