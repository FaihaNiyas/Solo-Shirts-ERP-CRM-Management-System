<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

/**
 * Phase 8A — set or clear a roll's per-roll low-stock reorder threshold. Null
 * clears it (the roll then never flags low stock at the roll level).
 */
final class FabricRollThresholdRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'low_stock_threshold_metres' => ['present', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }
}
