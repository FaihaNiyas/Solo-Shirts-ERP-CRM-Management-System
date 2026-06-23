<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the BalanceService summary array (all values in integer paise).
 *
 * @property array{invoiced_paise: int, paid_paise: int, credited_paise: int, outstanding_paise: int} $resource
 */
final class OutstandingBalanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invoiced_paise' => $this->resource['invoiced_paise'],
            'paid_paise' => $this->resource['paid_paise'],
            'credited_paise' => $this->resource['credited_paise'],
            'outstanding_paise' => $this->resource['outstanding_paise'],
        ];
    }
}
