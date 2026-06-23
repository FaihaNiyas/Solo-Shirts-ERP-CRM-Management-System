<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Finance\Http\Requests\IssueCreditNoteRequest;
use App\Modules\Finance\Http\Resources\CreditNoteResource;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Services\InvoiceService;
use Illuminate\Http\JsonResponse;

final class CreditNoteController extends BaseApiController
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function store(IssueCreditNoteRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('issueCreditNote', Invoice::class);

        /** @var User $actor */
        $actor = $request->user();

        $creditNote = $this->invoices->issueCreditNote(
            $invoice,
            (string) $request->string('reason'),
            $request->integer('total'),
            $actor,
        );

        return $this->respond((new CreditNoteResource($creditNote))->resolve(), 'Credit note issued', 201);
    }
}
