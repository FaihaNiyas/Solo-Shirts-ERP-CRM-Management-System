<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Resources\KanbanBoardResource;
use App\Modules\Production\Services\KanbanBoardService;
use App\Modules\Production\Services\StageSupervisorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KanbanBoardController extends BaseApiController
{
    public function __construct(
        private readonly KanbanBoardService $kanban,
        private readonly StageSupervisorService $supervisors,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OrderItem::class);

        $filters = [];

        if ($request->filled('order_id')) {
            $filters['order_id'] = $request->integer('order_id');
        }

        if ($request->filled('product_type')) {
            $filters['product_type'] = (string) $request->string('product_type');
        }

        // Text + single-value filters.
        foreach (['search', 'stage', 'priority', 'date_from', 'date_to'] as $key) {
            if ($request->filled($key)) {
                $filters[$key] = (string) $request->string($key);
            }
        }

        // Boolean toggles.
        foreach (['delayed', 'rework', 'ready'] as $key) {
            if ($request->boolean($key)) {
                $filters[$key] = true;
            }
        }

        // Stage scoping: "my section" for the caller, or a chosen supervisor's
        // sections. mine takes precedence over an explicit supervisor_id.
        if ($request->boolean('mine')) {
            /** @var User $actor */
            $actor = $request->user();
            $filters['stages'] = $this->supervisors->stagesForUser($actor->id);
        } elseif ($request->filled('supervisor_id')) {
            $filters['stages'] = $this->supervisors->stagesForUser((int) $request->integer('supervisor_id'));
        }

        $board = $this->kanban->board($filters);

        return $this->respond((new KanbanBoardResource($board))->resolve());
    }
}
