<?php

declare(strict_types=1);

namespace App\Modules\Printing\Jobs;

use App\Modules\Printing\Services\DocumentDataResolver;
use App\Modules\Printing\Services\PdfRenderer;
use App\Modules\Shared\Services\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Async wrapper for heavy PDFs (e.g. a multi-page GST invoice). Renders the
 * document off the request, then emits a notification so the requester can be
 * told the download is ready.
 */
final class RenderPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $kind,
        public readonly int $referenceId,
        public readonly ?int $generatedBy = null,
        public readonly ?string $notify = null,
    ) {}

    public function handle(
        DocumentDataResolver $resolver,
        PdfRenderer $renderer,
        NotificationDispatcher $notifications,
    ): void {
        $spec = $resolver->resolve($this->kind, $this->referenceId);
        $document = $renderer->render($spec, $this->generatedBy);

        $notifications->send('system', $this->notify ?? 'document-ready', [
            'template' => 'document_ready',
            'document_id' => $document->id,
            'kind' => $document->kind,
        ]);
    }
}
