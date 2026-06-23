<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Http\Requests\SaveDraftRequest;
use App\Modules\Order\Models\FrontDeskOrderDraft;
use App\Modules\Order\Services\FrontDeskDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Server-side Front Desk order drafts (Phase 6B). Gated by orders.create (which
 * Front Desk already holds). Drafts are user-scoped — a Front Desk user manages
 * only their own; Owner/Admin may view (but not edit) every draft in the branch.
 * All access is branch-scoped via the model's global scope.
 */
final class FrontDeskDraftController extends BaseApiController
{
    public function __construct(private readonly FrontDeskDraftService $drafts) {}

    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);

        $rows = $this->drafts->openForUser($actor);

        return $this->respond($rows->map(fn (FrontDeskOrderDraft $d): array => $this->summary($d, false))->all());
    }

    public function store(SaveDraftRequest $request): JsonResponse
    {
        $actor = $this->actor($request);

        $draft = $this->drafts->create($request->validated(), $actor);

        return $this->respond($this->summary($draft, true), 'Draft created', 201);
    }

    public function show(Request $request, FrontDeskOrderDraft $draft): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($draft->user_id === $actor->id || $actor->hasAnyRole(['Owner', 'Admin']), 403);

        return $this->respond($this->summary($draft, true));
    }

    public function update(SaveDraftRequest $request, FrontDeskOrderDraft $draft): JsonResponse
    {
        $actor = $this->actor($request);
        $this->ensureOwn($draft, $actor);
        abort_unless($draft->isOpen(), 409, 'This draft can no longer be edited.');

        $updated = $this->drafts->update($draft, $request->validated());

        return $this->respond($this->summary($updated, true), 'Draft saved');
    }

    public function destroy(Request $request, FrontDeskOrderDraft $draft): JsonResponse
    {
        $actor = $this->actor($request);
        $this->ensureOwn($draft, $actor);

        $this->drafts->discard($draft);

        return $this->respond(['id' => $draft->id, 'status' => $draft->status], 'Draft discarded');
    }

    public function convert(Request $request, FrontDeskOrderDraft $draft): JsonResponse
    {
        $actor = $this->actor($request);
        $this->ensureOwn($draft, $actor);

        $this->drafts->convert($draft);

        return $this->respond(['id' => $draft->id, 'status' => $draft->status], 'Draft converted');
    }

    private function actor(Request $request): User
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can('orders.create'), 403);

        return $actor;
    }

    private function ensureOwn(FrontDeskOrderDraft $draft, User $actor): void
    {
        abort_unless($draft->user_id === $actor->id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(FrontDeskOrderDraft $draft, bool $full): array
    {
        $base = [
            'id' => $draft->id,
            'title' => $draft->title ?? $draft->customer?->name,
            'status' => $draft->status,
            'current_step' => $draft->current_step,
            'completed_count' => $draft->completed_count,
            'total_items' => $draft->total_items,
            'customer_id' => $draft->customer_id,
            'customer_name' => $draft->customer?->name,
            'order_id' => $draft->order_id,
            'created_by' => $draft->user?->name,
            'last_saved_at' => $draft->last_saved_at?->toIso8601String(),
        ];

        if ($full) {
            $base['draft_payload'] = $draft->draft_payload;
        }

        return $base;
    }
}
