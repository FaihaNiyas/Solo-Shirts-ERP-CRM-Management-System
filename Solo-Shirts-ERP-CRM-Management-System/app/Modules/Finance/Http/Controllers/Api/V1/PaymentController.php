<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Finance\Exceptions\FinanceException;
use App\Modules\Finance\Http\Requests\RecordPaymentRequest;
use App\Modules\Finance\Http\Resources\PaymentResource;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentController extends BaseApiController
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Payment::query()->latest('paid_at');

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->integer('invoice_id'));
        }

        return $this->respond(PaymentResource::collection($query->get())->resolve());
    }

    public function store(RecordPaymentRequest $request): JsonResponse
    {
        $this->authorize('recordPayment', Invoice::class);

        $key = $request->header('Idempotency-Key');

        if ($key === null || trim($key) === '') {
            throw FinanceException::idempotencyKeyRequired();
        }

        /** @var User $actor */
        $actor = $request->user();
        /** @var Invoice $invoice */
        $invoice = Invoice::query()->findOrFail($request->integer('invoice_id'));

        $payment = $this->payments->record($invoice, $request->validated(), $key, $actor);

        return $this->respond((new PaymentResource($payment))->resolve(), 'Payment recorded', 201);
    }
}
