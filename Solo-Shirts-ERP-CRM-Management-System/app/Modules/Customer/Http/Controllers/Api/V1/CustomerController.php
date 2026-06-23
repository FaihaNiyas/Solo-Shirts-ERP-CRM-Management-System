<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Customer\Http\Requests\CreateCustomerRequest;
use App\Modules\Customer\Http\Requests\UpdateCustomerRequest;
use App\Modules\Customer\Http\Resources\CustomerListResource;
use App\Modules\Customer\Http\Resources\CustomerResource;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Http\Resources\OrderListResource;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Printing\Http\Resources\DocumentResource;
use App\Modules\Printing\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerController extends BaseApiController
{
    public function __construct(private readonly CustomerService $customers) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $term = $request->query('search');
        $perPage = max(1, min(100, $request->integer('per_page', 20)));
        $page = $this->customers->search(is_string($term) ? $term : null, $perPage);

        return $this->respondPaginated($page, CustomerListResource::collection($page->items())->resolve());
    }

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        /** @var User $actor */
        $actor = $request->user();
        $customer = $this->customers->create($request->validated(), $actor);

        return $this->respond((new CustomerResource($customer))->resolve(), 'Customer created', 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', Customer::class);

        $customer->load('familyMembers');

        return $this->respond((new CustomerResource($customer))->resolve());
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', Customer::class);

        /** @var User $actor */
        $actor = $request->user();
        $customer = $this->customers->update($customer, $request->validated(), $actor);

        return $this->respond((new CustomerResource($customer))->resolve(), 'Customer updated');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', Customer::class);

        $this->customers->delete($customer);

        return $this->respond(null, 'Customer deleted');
    }

    /**
     * Orders placed by this customer (newest first). The customer is global
     * (shared across branches); orders remain branch-scoped, so a branch sees the
     * orders it took for this customer while an Owner sees them across branches.
     */
    public function orders(Customer $customer): JsonResponse
    {
        $this->authorize('view', Customer::class);

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with('items')
            ->latest()
            ->get();

        return $this->respond(OrderListResource::collection($orders)->resolve());
    }

    /**
     * Every generated PDF connected to this customer — order job cards & packing
     * slips (order- and item-level), GST invoices, measurement cards and delivery
     * receipts — each with a fresh signed download URL. Surfaced on the customer
     * page so a document is never lost just because it was missed at intake.
     */
    public function documents(Customer $customer): JsonResponse
    {
        $this->authorize('view', Customer::class);

        $orderIds = Order::query()->where('customer_id', $customer->id)->pluck('id');
        $itemIds = OrderItem::query()->whereIn('order_id', $orderIds)->pluck('id');
        $invoiceIds = Invoice::query()->where('customer_id', $customer->id)->pluck('id');
        $versionIds = MeasurementVersion::query()
            ->whereIn('profile_id', MeasurementProfile::query()->where('customer_id', $customer->id)->select('id'))
            ->pluck('id');
        $deliveryIds = Delivery::query()->whereIn('order_id', $orderIds)->pluck('id');

        $refs = [
            [Order::class, $orderIds],
            [OrderItem::class, $itemIds],
            [Invoice::class, $invoiceIds],
            [MeasurementVersion::class, $versionIds],
            [Delivery::class, $deliveryIds],
        ];

        $documents = Document::query()
            ->where(function (Builder $outer) use ($refs): void {
                foreach ($refs as [$type, $ids]) {
                    $outer->orWhere(function (Builder $w) use ($type, $ids): void {
                        $w->where('reference_type', $type)->whereIn('reference_id', $ids);
                    });
                }
            })
            ->latest('generated_at')
            ->get();

        return $this->respond(DocumentResource::collection($documents)->resolve());
    }

    /**
     * Customer outstanding summary plus a per-invoice balance breakdown, computed
     * on demand from the append-only finance ledgers.
     */
    public function balance(Customer $customer, BalanceService $balances): JsonResponse
    {
        $this->authorize('view', Customer::class);

        $summary = $balances->outstandingForCustomer($customer->id);

        $invoices = Invoice::query()
            ->where('customer_id', $customer->id)
            ->latest('issued_at')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'status' => $invoice->status,
                'total_paise' => $invoice->total_paise,
                'balance_paise' => $balances->outstandingForInvoice($invoice),
            ])
            ->all();

        return $this->respond([
            'invoiced_paise' => $summary['invoiced_paise'],
            'paid_paise' => $summary['paid_paise'],
            'credited_paise' => $summary['credited_paise'],
            'outstanding_paise' => $summary['outstanding_paise'],
            'invoices' => $invoices,
        ]);
    }

    /**
     * A simple chronological activity feed for the customer 360 view, composed
     * from orders placed, invoices issued and payments received.
     */
    public function timeline(Customer $customer): JsonResponse
    {
        $this->authorize('view', Customer::class);

        $events = [];

        Order::query()
            ->where('customer_id', $customer->id)
            ->get(['id', 'order_code', 'created_at'])
            ->each(function (Order $order) use (&$events): void {
                $events[] = [
                    'type' => 'order',
                    'event' => "Order {$order->order_code} placed",
                    'at' => $order->created_at,
                ];
            });

        Invoice::query()
            ->where('customer_id', $customer->id)
            ->with('payments')
            ->get()
            ->each(function (Invoice $invoice) use (&$events): void {
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

        usort($events, function (array $a, array $b): int {
            return ($b['at']->timestamp ?? 0) <=> ($a['at']->timestamp ?? 0);
        });

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
}
