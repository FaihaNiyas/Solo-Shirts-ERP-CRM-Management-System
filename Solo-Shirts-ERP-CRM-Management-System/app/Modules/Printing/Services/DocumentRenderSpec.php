<?php

declare(strict_types=1);

namespace App\Modules\Printing\Services;

/**
 * Everything PdfRenderer needs to render and file one document: which Blade view
 * and data to render, what the document references, the owning branch, and
 * whether the document is heavy enough to belong on the queue.
 */
final class DocumentRenderSpec
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $referenceType,
        public readonly int $referenceId,
        public readonly int $branchId,
        public readonly string $view,
        public readonly array $data,
        public readonly bool $heavy,
    ) {}
}
