<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Resources;

use App\Modules\Finance\Models\Invoice;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin Invoice
 */
final class InvoiceResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'invoice_number' => $this->invoice_no,
            'order_id' => $this->order_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->name,
            'gst_treatment' => $this->gst_treatment,
            'subtotal_paise' => $this->subtotal_paise,
            'cgst_paise' => $this->cgst_paise,
            'sgst_paise' => $this->sgst_paise,
            'igst_paise' => $this->igst_paise,
            'delivery_charges_paise' => $this->delivery_charges_paise,
            'discount_paise' => $this->discount_paise,
            'total_paise' => $this->total_paise,
            'total_amount' => $this->total_paise ? round($this->total_paise / 100, 2) : 0,
            'paid_amount' => 0,
            'balance_amount' => $this->total_paise ? round($this->total_paise / 100, 2) : 0,
            'status' => $this->status,
            'issued_at' => $this->date($this->issued_at),
            'created_at' => $this->date($this->issued_at),
            'pdf_path' => $this->pdf_path,
            'lines' => InvoiceLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
