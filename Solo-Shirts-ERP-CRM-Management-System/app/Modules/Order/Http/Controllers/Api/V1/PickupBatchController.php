<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\PaymentAllocation;
use App\Modules\Order\Http\Requests\CollectPickupPaymentRequest;
use App\Modules\Order\Http\Requests\CreatePickupBatchRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\PickupBatch;
use App\Modules\Order\Models\PickupBatchItem;
use App\Modules\Order\Services\PickupBatchService;
use App\Modules\Order\Exceptions\PickupException;
use App\Modules\Printing\Http\Resources\DocumentResource;
use App\Modules\Printing\Models\Document;
use App\Modules\Printing\Services\DocumentRenderSpec;
use App\Modules\Printing\Services\PdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pickup batches: create a collection event for selected ready shirts, collect
 * its payment, hand it over (pay-now only), and print its receipt. All actions
 * are order-scoped (cross-branch route binding 404s) and reuse the existing
 * Front Desk permissions — no unpaid handover path exists here.
 */
final class PickupBatchController extends BaseApiController
{
    public function __construct(
        private readonly PickupBatchService $service,
        private readonly PdfRenderer $renderer,
    ) {}

    /** All pickup batches for the order, newest first (pickup history). */
    public function index(Order $order): JsonResponse
    {
        $this->authorize('view', Order::class);

        $batches = $order->pickupBatches()->latest('id')->get()
            ->map(fn (PickupBatch $b): array => $this->service->summary($b))
            ->all();

        return $this->respond($batches);
    }

    public function store(CreatePickupBatchRequest $request, Order $order): JsonResponse
    {
        $this->authorize('handover', Order::class);

        /** @var User $actor */
        $actor = $request->user();

        $batch = $this->service->create(
            $order,
            array_map('intval', $request->validated('item_ids')),
            (string) $request->input('pickup_type', PickupBatch::TYPE_COUNTER_PICKUP),
            $actor,
        );

        return $this->respond($this->service->summary($batch), 'Pickup batch created', 201);
    }

    public function show(Order $order, PickupBatch $pickupBatch): JsonResponse
    {
        $this->authorize('view', Order::class);

        return $this->respond($this->service->summary($pickupBatch));
    }

    public function payment(CollectPickupPaymentRequest $request, Order $order, PickupBatch $pickupBatch): JsonResponse
    {
        $this->authorize('collectPayment', Order::class);

        /** @var User $actor */
        $actor = $request->user();

        $key = $request->header('Idempotency-Key');
        $key = is_string($key) && trim($key) !== '' ? $key : (string) Str::uuid();

        $result = $this->service->collectPayment(
            $pickupBatch,
            (int) round(((float) $request->input('amount')) * 100),
            (string) $request->input('method'),
            $request->input('reference'),
            $key,
            $actor,
        );

        return $this->respond($result, 'Payment recorded', 201);
    }

    public function handover(Request $request, Order $order, PickupBatch $pickupBatch): JsonResponse
    {
        $this->authorize('handover', Order::class);

        /** @var User $actor */
        $actor = $request->user();

        return $this->respond($this->service->handover($pickupBatch, $actor), 'Pickup handed over');
    }

    public function receipt(Request $request, Order $order, PickupBatch $pickupBatch): JsonResponse
    {
        $this->authorize('view', Order::class);

        if (! in_array($pickupBatch->status, [PickupBatch::STATUS_PAID, PickupBatch::STATUS_HANDED_OVER], true)) {
            throw PickupException::notPaid();
        }

        /** @var User $actor */
        $actor = $request->user();

        $pickupBatch->loadMissing('items.orderItem', 'customer', 'order.branch', 'handedOverBy');
        $invoice = Invoice::query()->where('order_id', $pickupBatch->order_id)->latest('id')->first();

        // Payments attributed to THIS batch (selected-item allocations).
        $paymentIds = PaymentAllocation::query()
            ->where('pickup_batch_id', $pickupBatch->id)
            ->distinct()
            ->pluck('payment_id');
        $payments = $invoice?->payments()->whereIn('id', $paymentIds)->get() ?? collect();

        $orderBalancePaise = (int) $invoice?->total_paise
            - (int) ($invoice?->payments()->sum('amount_paise') ?? 0);

        $spec = new DocumentRenderSpec(
            kind: Document::KIND_PICKUP_RECEIPT,
            referenceType: PickupBatch::class,
            referenceId: $pickupBatch->id,
            branchId: $pickupBatch->branch_id,
            view: 'pdfs.pickup_receipt',
            data: [
                'batch' => $pickupBatch,
                'order' => $pickupBatch->order,
                'branch' => $pickupBatch->order->branch,
                'customer' => $pickupBatch->customer,
                'invoiceNo' => $invoice?->invoice_no,
                'items' => $pickupBatch->items,
                'payments' => $payments,
                'orderBalancePaise' => max(0, $orderBalancePaise),
                'staff' => $pickupBatch->handedOverBy?->name ?? $actor->name,
            ],
            heavy: false,
        );

        $document = $this->renderer->render($spec, $actor->id);

        return $this->respond([
            ...(new DocumentResource($document))->resolve(),
            'batch_no' => $pickupBatch->batch_no,
            'receipt_no' => $pickupBatch->receipt_no,
        ], 'Pickup receipt ready', 201);
    }
}
