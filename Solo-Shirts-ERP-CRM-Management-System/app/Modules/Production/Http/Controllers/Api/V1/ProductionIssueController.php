<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\ReportIssueRequest;
use App\Modules\Production\Http\Requests\ResolveIssueRequest;
use App\Modules\Production\Http\Resources\ProductionIssueResource;
use App\Modules\Production\Models\ProductionIssue;
use App\Modules\Production\Services\ProductionIssueService;
use Illuminate\Http\JsonResponse;

/**
 * Production issues (Kanban Phase B) — text-only problem reports raised against an
 * item. Reporting/resolving never moves the item's production state. Branch
 * isolation is enforced by the OrderItem / ProductionIssue global scopes.
 */
final class ProductionIssueController extends BaseApiController
{
    public function __construct(private readonly ProductionIssueService $issues) {}

    /** Issues raised against this item, newest first. */
    public function index(OrderItem $item): JsonResponse
    {
        $this->authorize('view', OrderItem::class);

        $issues = ProductionIssue::query()
            ->where('order_item_id', $item->id)
            ->with(['reporter:id,name', 'resolver:id,name'])
            ->latest('id')
            ->get();

        return $this->respond(ProductionIssueResource::collection($issues)->resolve());
    }

    public function store(ReportIssueRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('reportIssue', OrderItem::class);

        /** @var User $actor */
        $actor = $request->user();

        $issue = $this->issues->report(
            $item,
            (string) $request->string('issue_type'),
            (string) $request->string('description'),
            $actor,
        );

        $issue->load(['reporter:id,name']);

        return $this->respond((new ProductionIssueResource($issue))->resolve(), 'Issue reported', 201);
    }

    public function resolve(ResolveIssueRequest $request, ProductionIssue $issue): JsonResponse
    {
        $this->authorize('resolveIssue', OrderItem::class);

        /** @var User $actor */
        $actor = $request->user();
        $notes = $request->filled('notes') ? (string) $request->string('notes') : null;

        $issue = $this->issues->resolve($issue, $notes, $actor);
        $issue->load(['reporter:id,name', 'resolver:id,name']);

        return $this->respond((new ProductionIssueResource($issue))->resolve(), 'Issue resolved');
    }
}
