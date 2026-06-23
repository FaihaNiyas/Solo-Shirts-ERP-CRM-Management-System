<?php

declare(strict_types=1);

namespace App\Modules\Printing\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Printing\Http\Requests\RegenerateDocumentRequest;
use App\Modules\Printing\Http\Resources\DocumentResource;
use App\Modules\Printing\Jobs\RenderPdfJob;
use App\Modules\Printing\Models\Document;
use App\Modules\Printing\Services\DocumentDataResolver;
use App\Modules\Printing\Services\PdfRenderer;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentController extends BaseApiController
{
    public function __construct(
        private readonly DocumentDataResolver $resolver,
        private readonly PdfRenderer $renderer,
    ) {}

    /**
     * Recently generated documents (newest first), each carrying a fresh signed
     * download URL. Branch-scoped via the global BranchScope on Document.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $documents = Document::query()
            ->latest('generated_at')
            ->limit(100)
            ->get();

        return $this->respond(DocumentResource::collection($documents)->resolve());
    }

    /**
     * Public but signature-protected: the only way to fetch the bytes is a fresh
     * temporary signed URL. The raw disk path is never exposed.
     */
    public function download(Document $document): StreamedResponse
    {
        $disk = $document->storageDisk();

        abort_unless($disk->exists($document->path), 404);

        return $disk->response($document->path);
    }

    public function regenerate(RegenerateDocumentRequest $request): JsonResponse
    {
        $this->authorize('regenerate', Document::class);

        /** @var User $actor */
        $actor = $request->user();
        $kind = (string) $request->string('kind');
        $referenceId = $request->integer('reference_id');

        $spec = $this->resolver->resolve($kind, $referenceId);

        // Heavy documents (e.g. a multi-page invoice) render off the request.
        if ($spec->heavy) {
            RenderPdfJob::dispatch($kind, $referenceId, $actor->id);

            return $this->respond(
                ['status' => 'queued', 'kind' => $kind, 'reference_id' => $referenceId],
                'Document queued for rendering',
                202,
            );
        }

        $document = $this->renderer->render($spec, $actor->id);

        return $this->respond((new DocumentResource($document))->resolve(), 'Document ready', 201);
    }
}
