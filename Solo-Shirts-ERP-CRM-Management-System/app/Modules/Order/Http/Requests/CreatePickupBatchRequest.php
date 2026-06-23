<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Order\Models\PickupBatch;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Create-pickup-batch payload. Selected ready shirts (item_ids) for one
 * collection event. pickup_type defaults to counter_pickup.
 */
final class CreatePickupBatchRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer'],
            'pickup_type' => ['nullable', Rule::in([
                PickupBatch::TYPE_COUNTER_PICKUP,
                PickupBatch::TYPE_HOME_DELIVERY,
                PickupBatch::TYPE_COURIER,
            ])],
        ];
    }
}
