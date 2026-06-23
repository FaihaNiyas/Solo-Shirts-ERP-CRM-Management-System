<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Order\Http\Resources\JobCardResource;
use App\Modules\Order\Models\Order;
use Illuminate\Http\JsonResponse;

final class JobCardController extends BaseApiController
{
    /**
     * Returns the structured job-card data. Phase 16 will render this to a PDF.
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('printJobCard', Order::class);

        $order->load('customer', 'items.measurementVersion');

        return $this->respond((new JobCardResource($order))->resolve(), 'Job card');
    }
}
