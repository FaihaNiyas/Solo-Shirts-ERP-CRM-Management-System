<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Http\Requests\CancelOrderRequest;
use App\Modules\Order\Http\Requests\ConfirmOrderRequest;
use App\Modules\Order\Http\Requests\CreateOrderRequest;
use App\Modules\Order\Http\Requests\UpdateOrderRequest;
use App\Modules\Order\Http\Resources\OrderListResource;
use App\Modules\Order\Http\Resources\OrderResource;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\PickupBatch;
use App\Modules\Order\Services\OrderConfirmationService;
use App\Modules\Order\Services\OrderProgressSummary;
use App\Modules\Order\Services\OrderService;
use App\Modules\Printing\Http\Resources\DocumentResource;
use App\Modules\Printing\Models\Document;
use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends BaseApiController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly OrderConfirmationService $confirmation,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()
            ->with(['items', 'customer'])
            ->withSum('invoices', 'total_paise')
            ->latest('id');

        if ($request->filled('customer')) {
            $query->where('customer_id', $request->integer('customer'));
        }
        if ($request->filled('q')) {
            $term = (string) $request->query('q');
            $digits = preg_replace('/\D/', '', $term) ?? '';
            $query->where(function ($builder) use ($term, $digits): void {
                $builder
                    ->where('order_code', 'like', '%' . $term . '%')
                    ->orWhereHas('customer', function ($c) use ($term, $digits): void {
                        $c->where('name', 'like', '%' . $term . '%');
                        if ($digits !== '') {
                            $c->orWhere('phone_search', 'like', '%' . $digits . '%')
                              ->orWhere('phone_last4', 'like', '%' . $digits . '%');
                        }
                    });
            });
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $perPage = max(1, min(100, $request->integer('per_page', 20)));
        $page = $query->paginate($perPage);

        return $this->respondPaginated($page, OrderListResource::collection($page->items())->resolve());
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        /** @var User $actor */
        $actor = $request->user();
        $order = $this->orders->createOrder($request->validated(), $actor);

        return $this->respond((new OrderResource($order))->resolve(), 'Order created', 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', Order::class);

        $order->load('items', 'customer', 'branch')->loadSum('invoices', 'total_paise');

        return $this->respond((new OrderResource($order))->resolve());
    }

    /**
     * Every rendered PDF tied to this order — its invoices, per-item job cards and
     * measurement cards, pickup receipts and delivery receipts — newest first, each
     * with a fresh signed download URL. Branch-scoped via the Document global scope.
     */
    public function documents(Order $order): JsonResponse
    {
        $this->authorize('view', Order::class);

        $items = $order->items()->get(['id', 'item_code', 'measurement_version_id']);
        $itemIds = $items->pluck('id');
        $versionIds = $items->pluck('measurement_version_id')->filter()->unique();
        $invoiceIds = $order->invoices()->pluck('id');
        $batchIds = $order->pickupBatches()->pluck('id');
        $deliveryIds = Delivery::query()->where('order_id', $order->id)->pluck('id');

        $refs = [
            [Order::class, collect([$order->id])],
            [OrderItem::class, $itemIds],
            [Invoice::class, $invoiceIds],
            [MeasurementVersion::class, $versionIds],
            [PickupBatch::class, $batchIds],
            [Delivery::class, $deliveryIds],
        ];

        $documents = Document::query()
            ->where(function (Builder $outer) use ($refs): void {
                foreach ($refs as [$type, $ids]) {
                    if ($ids->isEmpty()) {
                        continue;
                    }
                    $outer->orWhere(function (Builder $w) use ($type, $ids): void {
                        $w->where('reference_type', $type)->whereIn('reference_id', $ids);
                    });
                }
            })
            ->latest('generated_at')
            ->get();

        // Human label for what each document is *about* — the sub-order's item code
        // for job/measurement cards, the invoice/batch number otherwise — so the UI
        // can show "Job Card · SHIRT-003" instead of an opaque "Job Card".
        $itemCodeById = $items->pluck('item_code', 'id');
        $itemCodeByVersion = $items->whereNotNull('measurement_version_id')->pluck('item_code', 'measurement_version_id');
        $invoiceNoById = $order->invoices()->pluck('invoice_no', 'id');
        $batchNoById = $order->pickupBatches()->pluck('batch_no', 'id');

        $payload = $documents->map(function (Document $doc) use ($itemCodeById, $itemCodeByVersion, $invoiceNoById, $batchNoById): array {
            $reference = match ($doc->reference_type) {
                OrderItem::class => $itemCodeById[$doc->reference_id] ?? null,
                MeasurementVersion::class => $itemCodeByVersion[$doc->reference_id] ?? null,
                Invoice::class => $invoiceNoById[$doc->reference_id] ?? null,
                PickupBatch::class => $batchNoById[$doc->reference_id] ?? null,
                default => null,
            };

            return [
                ...(new DocumentResource($doc))->resolve(),
                'reference_label' => $reference,
                'order_item_id' => $doc->reference_type === OrderItem::class ? $doc->reference_id : null,
            ];
        })->all();

        return $this->respond($payload);
    }

    /**
     * Chronological history for the order — placement, invoices, payments, per-item
     * production transitions and pickup handovers — newest first. Aggregated
     * server-side so the order page needs only the same `view` permission, rather
     * than the manager-grade activity-log permission.
     */
    public function timeline(Order $order): JsonResponse
    {
        $this->authorize('view', Order::class);

        /** @var array<int, array{type: string, event: string, at: \Illuminate\Support\Carbon|null}> $events */
        $events = [];

        $events[] = [
            'type' => 'order',
            'event' => "Order {$order->order_code} placed",
            'at' => $order->created_at,
        ];

        $order->invoices()->with('payments')->get()->each(function (Invoice $invoice) use (&$events): void {
            $events[] = [
                'type' => 'invoice',
                'event' => "Invoice {$invoice->invoice_no} issued",
                'at' => $invoice->issued_at,
            ];

            foreach ($invoice->payments as $payment) {
                $rupees = number_format($payment->amount_paise / 100, 2);
                $events[] = [
                    'type' => 'payment',
                    'event' => "Payment of ₹{$rupees} received",
                    'at' => $payment->created_at,
                ];
            }
        });

        $items = $order->items()->get(['id', 'item_code']);
        $codeById = $items->pluck('item_code', 'id');

        ProductionTransition::query()
            ->whereIn('order_item_id', $items->pluck('id'))
            ->orderBy('occurred_at')
            ->get()
            ->each(function (ProductionTransition $t) use (&$events, $codeById): void {
                $code = $codeById[$t->order_item_id] ?? "#{$t->order_item_id}";
                $events[] = [
                    'type' => 'production',
                    'event' => "{$code} → " . OrderProgressSummary::label($t->to_state),
                    'at' => $t->occurred_at,
                ];
            });

        $order->pickupBatches()->whereNotNull('handed_over_at')->get()
            ->each(function (PickupBatch $b) use (&$events): void {
                $events[] = [
                    'type' => 'pickup',
                    'event' => "Pickup {$b->batch_no} handed over",
                    'at' => $b->handed_over_at,
                ];
            });

        usort($events, fn (array $a, array $b): int => ($b['at']?->timestamp ?? 0) <=> ($a['at']?->timestamp ?? 0));

        $feed = [];
        foreach ($events as $index => $event) {
            $feed[] = [
                'id' => $index + 1,
                'type' => $event['type'],
                'event' => $event['event'],
                'created_at' => $event['at']?->toISOString(),
            ];
        }

        return $this->respond($feed);
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', Order::class);

        /** @var User $actor */
        $actor = $request->user();
        $order = $this->orders->updateOrder($order, $request->validated(), $actor);

        return $this->respond((new OrderResource($order))->resolve(), 'Order updated');
    }

    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', Order::class);

        $reason = $request->filled('reason') ? (string) $request->string('reason') : null;
        $order = $this->orders->cancelOrder($order, $reason);

        return $this->respond((new OrderResource($order->load('items')))->resolve(), 'Order cancelled');
    }

    /**
     * Final confirm — promotes an intake order to Order Received and releases it
     * to production. Requires a box + generated PDF on every sub-order. When
     * pricing/payment are supplied it also creates the invoice and records the
     * advance, returning an invoice + payment + balance summary.
     */
    public function confirm(ConfirmOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('confirm', Order::class);

        /** @var User $actor */
        $actor = $request->user();

        $result = $this->confirmation->confirm(
            $order,
            $actor,
            (array) ($request->input('pricing') ?? []),
            (array) ($request->input('payment') ?? []),
        );

        return $this->respond($this->confirmationSummary($result), 'Order confirmed');
    }

    /**
     * @param  array{order: Order, invoice: mixed, payment: mixed, balance_paise: int}  $result
     * @return array<string, mixed>
     */
    private function confirmationSummary(array $result): array
    {
        $order = $result['order'];
        $invoice = $result['invoice'];
        $payment = $result['payment'];
        $balancePaise = (int) $result['balance_paise'];

        return [
            'order' => (new OrderResource($order))->resolve(),
            'invoice' => $invoice === null ? null : [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_no,
                'total_amount' => $invoice->total_paise / 100,
                'paid_amount' => ($invoice->total_paise - $balancePaise) / 100,
                'balance_amount' => $balancePaise / 100,
                'status' => $invoice->status,
            ],
            'payment' => $payment === null ? null : [
                'id' => $payment->id,
                'amount' => $payment->amount_paise / 100,
                'method' => $payment->method,
                'status' => 'recorded',
            ],
        ];
    }
}
