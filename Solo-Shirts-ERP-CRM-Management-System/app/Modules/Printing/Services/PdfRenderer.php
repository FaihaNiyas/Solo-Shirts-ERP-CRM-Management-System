<?php

declare(strict_types=1);

namespace App\Modules\Printing\Services;

use App\Modules\Printing\Exceptions\PrintingException;
use App\Modules\Printing\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Throwable;

/**
 * Renders a Blade view to a PDF via DomPDF, content-addresses the bytes, and
 * files them. Identical input yields an identical content_hash, so a re-render
 * returns the existing Document/file instead of duplicating storage.
 */
final class PdfRenderer
{
    public function render(DocumentRenderSpec $spec, ?int $generatedBy = null): Document
    {
        // Hash the rendered HTML (the deterministic input), not the PDF bytes —
        // DomPDF stamps a creation date into its output, which would otherwise
        // make every render hash differently and defeat dedupe.
        $html = View::make($spec->view, $spec->data)->render();
        $hash = hash('sha256', $html);

        $existing = $this->findExisting($spec, $hash);

        if ($existing !== null) {
            return $existing;
        }

        $bytes = Pdf::loadHTML($html)->output();
        $disk = $this->disk();
        $path = sprintf('documents/%s/%s.pdf', $spec->kind, $hash);

        try {
            Storage::disk($disk)->put($path, $bytes);
        } catch (Throwable) {
            throw PrintingException::storageUnavailable();
        }

        try {
            return Document::query()->create([
                'branch_id' => $spec->branchId,
                'kind' => $spec->kind,
                'reference_type' => $spec->referenceType,
                'reference_id' => $spec->referenceId,
                'disk' => $disk,
                'path' => $path,
                'content_hash' => $hash,
                'size_bytes' => strlen($bytes),
                'generated_by' => $generatedBy,
                'generated_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // A concurrent render filed the same content first.
            return $this->findExisting($spec, $hash) ?? throw PrintingException::storageUnavailable();
        }
    }

    private function findExisting(DocumentRenderSpec $spec, string $hash): ?Document
    {
        return Document::query()
            ->where('kind', $spec->kind)
            ->where('reference_type', $spec->referenceType)
            ->where('reference_id', $spec->referenceId)
            ->where('content_hash', $hash)
            ->first();
    }

    private function disk(): string
    {
        $disk = config('documents.disk') ?? config('filesystems.default');

        return is_string($disk) ? $disk : 'local';
    }
}
