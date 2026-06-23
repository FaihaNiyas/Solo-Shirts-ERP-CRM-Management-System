<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Resources;

use App\Modules\Finance\Models\InvoiceLine;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin InvoiceLine
 */
final class InvoiceLineResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'description' => $this->description,
            'hsn_code' => $this->hsn_code,
            'quantity' => $this->quantity,
            'unit_price_paise' => $this->unit_price_paise,
            'taxable_paise' => $this->taxable_paise,
            'gst_rate' => $this->gst_rate,
            'tax_paise' => $this->tax_paise,
        ];
    }
}
