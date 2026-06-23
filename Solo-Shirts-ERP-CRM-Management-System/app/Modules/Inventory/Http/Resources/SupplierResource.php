<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Inventory\Models\Supplier;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin Supplier
 */
final class SupplierResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'gstin' => $this->gstin,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'payment_terms' => $this->payment_terms,
            'is_active' => $this->is_active,
        ];
    }
}
