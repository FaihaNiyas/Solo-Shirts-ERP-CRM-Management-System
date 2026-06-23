<?php

declare(strict_types=1);

namespace App\Modules\Printing\Services;

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Models\Order;
use App\Modules\Printing\Exceptions\PrintingException;
use App\Modules\Printing\Models\Document;

/**
 * Maps a (kind, reference id) pair to a concrete render spec: it loads the
 * referenced aggregate, picks the Blade view, and flags heavy documents (a GST
 * invoice with many lines) so the caller can route them to the queue.
 */
final class DocumentDataResolver
{
    public const HEAVY_LINE_THRESHOLD = 50;

    public function resolve(string $kind, int $referenceId): DocumentRenderSpec
    {
        return match ($kind) {
            Document::KIND_JOB_CARD => $this->order($kind, $referenceId, 'pdfs.job_card'),
            Document::KIND_PACKING_SLIP => $this->order($kind, $referenceId, 'pdfs.packing_slip'),
            Document::KIND_GST_INVOICE => $this->invoice($referenceId),
            Document::KIND_MEASUREMENT_CARD => $this->measurementCard($referenceId),
            Document::KIND_DELIVERY_RECEIPT => $this->deliveryReceipt($referenceId),
            default => throw PrintingException::unknownKind($kind),
        };
    }

    private function order(string $kind, int $referenceId, string $view): DocumentRenderSpec
    {
        /** @var Order|null $order */
        $order = Order::query()->with(['items', 'customer'])->find($referenceId);

        if ($order === null) {
            throw PrintingException::referenceNotFound();
        }

        return new DocumentRenderSpec(
            kind: $kind,
            referenceType: Order::class,
            referenceId: $order->id,
            branchId: $order->branch_id,
            view: $view,
            data: ['order' => $order],
            heavy: false,
        );
    }

    private function invoice(int $referenceId): DocumentRenderSpec
    {
        /** @var Invoice|null $invoice */
        $invoice = Invoice::query()->with(['lines', 'customer', 'order'])->find($referenceId);

        if ($invoice === null) {
            throw PrintingException::referenceNotFound();
        }

        return new DocumentRenderSpec(
            kind: Document::KIND_GST_INVOICE,
            referenceType: Invoice::class,
            referenceId: $invoice->id,
            branchId: $invoice->branch_id,
            view: 'pdfs.gst_invoice',
            data: ['invoice' => $invoice],
            heavy: $invoice->lines->count() > self::HEAVY_LINE_THRESHOLD,
        );
    }

    private function measurementCard(int $referenceId): DocumentRenderSpec
    {
        /** @var MeasurementVersion|null $version */
        $version = MeasurementVersion::query()->with('profile.customer')->find($referenceId);

        if ($version === null) {
            throw PrintingException::referenceNotFound();
        }

        return new DocumentRenderSpec(
            kind: Document::KIND_MEASUREMENT_CARD,
            referenceType: MeasurementVersion::class,
            referenceId: $version->id,
            branchId: $version->branch_id,
            view: 'pdfs.measurement_card',
            data: ['version' => $version],
            heavy: false,
        );
    }

    private function deliveryReceipt(int $referenceId): DocumentRenderSpec
    {
        /** @var Delivery|null $delivery */
        $delivery = Delivery::query()->with('order.customer')->find($referenceId);

        if ($delivery === null) {
            throw PrintingException::referenceNotFound();
        }

        return new DocumentRenderSpec(
            kind: Document::KIND_DELIVERY_RECEIPT,
            referenceType: Delivery::class,
            referenceId: $delivery->id,
            branchId: $delivery->branch_id,
            view: 'pdfs.delivery_receipt',
            data: ['delivery' => $delivery],
            heavy: false,
        );
    }
}
