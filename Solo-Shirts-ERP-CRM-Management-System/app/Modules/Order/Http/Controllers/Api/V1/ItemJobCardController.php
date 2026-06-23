<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Printing\Http\Resources\DocumentResource;
use App\Modules\Printing\Models\Document;
use App\Modules\Printing\Services\DocumentRenderSpec;
use App\Modules\Printing\Services\PdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Per-sub-order job card / measurement sheet. ONE PDF per order item — with the
 * item's box code and that shirt's measurements only. Returns a Document with a
 * fresh signed download URL.
 */
final class ItemJobCardController extends BaseApiController
{
    public function __construct(
        private readonly PdfRenderer $renderer,
    ) {}

    public function show(Request $request, Order $order, OrderItem $item): JsonResponse
    {
        $this->authorize('printJobCard', Order::class);

        /** @var User $actor */
        $actor = $request->user();

        $order->loadMissing('customer', 'branch');
        $item->loadMissing('measurementVersion');

        $design = is_array($item->design_notes) ? $item->design_notes : [];

        $spec = new DocumentRenderSpec(
            kind: Document::KIND_JOB_CARD,
            referenceType: OrderItem::class,
            referenceId: $item->id,
            branchId: $item->branch_id,
            view: 'pdfs.item_job_card',
            data: [
                'order' => $order,
                'item' => $item,
                'version' => $item->measurementVersion,
                'design' => $design,
                'preparedBy' => $actor->name,
            ],
            heavy: false,
        );

        $document = $this->renderer->render($spec, $actor->id);

        return $this->respond([
            ...(new DocumentResource($document))->resolve(),
            'order_item_id' => $item->id,
            'item_code' => $item->item_code,
            'pdf_status' => 'generated',
        ], 'Job card ready', 201);
    }
}
