<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Finance\Http\Requests\CreateInvoiceRequest;
use App\Modules\Finance\Http\Resources\InvoiceResource;
use App\Modules\Finance\Http\Resources\OutstandingBalanceResource;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Finance\Services\InvoiceService;
use App\Modules\Order\Models\Order;
use App\Modules\Printing\Http\Resources\DocumentResource;
use App\Modules\Printing\Models\Document;
use App\Modules\Printing\Services\DocumentRenderSpec;
use App\Modules\Printing\Services\PdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InvoiceController extends BaseApiController
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly BalanceService $balances,
        private readonly PdfRenderer $renderer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()->with('customer')->latest('issued_at');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('from')) {
            $query->where('issued_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->where('issued_at', '<=', $request->date('to'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', (int) $request->input('customer_id'));
        }

        return $this->respond(InvoiceResource::collection($query->get())->resolve());
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        /** @var User $actor */
        $actor = $request->user();

        $invoice = $this->invoices->create($request->validated(), $actor);

        return $this->respond((new InvoiceResource($invoice))->resolve(), 'Invoice generated', 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', Invoice::class);

        return $this->respond((new InvoiceResource($invoice->load('lines')))->resolve());
    }

    /**
     * Render the GST tax invoice on demand and return a fresh signed download URL.
     * The PdfRenderer is content-addressed, so repeat calls for an unchanged invoice
     * reuse the same stored file instead of re-rendering.
     */
    public function pdf(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('downloadInvoice', Invoice::class);

        $invoice->loadMissing(['lines', 'customer', 'order', 'branch']);

        $spec = new DocumentRenderSpec(
            kind: Document::KIND_GST_INVOICE,
            referenceType: Invoice::class,
            referenceId: $invoice->id,
            branchId: $invoice->branch_id,
            view: 'pdfs.gst_invoice',
            data: ['invoice' => $invoice],
            heavy: false,
        );

        $document = $this->renderer->render($spec, $request->user()?->id);

        return $this->respond([
            ...(new DocumentResource($document))->resolve(),
            'invoice_no' => $invoice->invoice_no,
        ], 'Invoice ready', 201);
    }

    public function orderOutstanding(Order $order): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        return $this->respond(
            (new OutstandingBalanceResource($this->balances->outstandingForOrder($order->id)))->resolve()
        );
    }

    /**
     * Receivables aged by customer: every customer with a non-zero balance on an
     * open invoice, with the count, total still due and the oldest open date.
     */
    public function outstanding(): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])
            ->with('customer')
            ->get();

        /** @var array<int, array<string, mixed>> $byCustomer */
        $byCustomer = [];

        foreach ($invoices as $invoice) {
            $balance = $this->balances->outstandingForInvoice($invoice);
            if ($balance <= 0) {
                continue;
            }

            $customerId = $invoice->customer_id;

            if (!isset($byCustomer[$customerId])) {
                $byCustomer[$customerId] = [
                    'id' => $customerId,
                    'customer_id' => $customerId,
                    'customer_name' => $invoice->customer->name ?? 'Unknown',
                    'invoice_count' => 0,
                    'total_outstanding_paise' => 0,
                    'oldest_invoice_date' => $invoice->issued_at,
                ];
            }

            $byCustomer[$customerId]['invoice_count']++;
            $byCustomer[$customerId]['total_outstanding_paise'] += $balance;

            if ($invoice->issued_at->lt($byCustomer[$customerId]['oldest_invoice_date'])) {
                $byCustomer[$customerId]['oldest_invoice_date'] = $invoice->issued_at;
            }
        }

        $rows = array_map(function (array $row): array {
            $row['oldest_invoice_date'] = $row['oldest_invoice_date']?->toISOString();

            return $row;
        }, array_values($byCustomer));

        usort($rows, fn (array $a, array $b): int => $b['total_outstanding_paise'] <=> $a['total_outstanding_paise']);

        return $this->respond($rows);
    }
}
