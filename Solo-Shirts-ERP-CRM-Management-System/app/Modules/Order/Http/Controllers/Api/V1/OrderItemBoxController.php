<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Http\Requests\PrintLogRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\PrintLog;
use Illuminate\Http\JsonResponse;

/**
 * Front Desk per-sub-order print logging. Production box assignment was removed
 * from the workflow; this controller now only records job-card prints/reprints.
 */
final class OrderItemBoxController extends BaseApiController
{
    public function printLog(PrintLogRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        $this->authorize('printJobCard', Order::class);

        /** @var User $actor */
        $actor = $request->user();

        $log = PrintLog::query()->create([
            'document_id' => $request->input('document_id'),
            'order_item_id' => $item->id,
            'printed_by' => $actor->id,
            'printed_at' => now(),
            'is_reprint' => $request->boolean('is_reprint'),
            'reason' => $request->input('reason'),
        ]);

        return $this->respond([
            'id' => $log->id,
            'order_item_id' => $item->id,
            'is_reprint' => $log->is_reprint,
            'printed_at' => $log->printed_at->toIso8601String(),
        ], 'Print logged', 201);
    }
}
