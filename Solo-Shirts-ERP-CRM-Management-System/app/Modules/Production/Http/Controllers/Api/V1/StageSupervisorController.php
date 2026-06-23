<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\AssignSupervisorRequest;
use App\Modules\Production\Http\Resources\StageSupervisorResource;
use App\Modules\Production\Models\ProductionStageSupervisor;
use App\Modules\Production\Services\StageSupervisorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Production section-supervisor assignments (Kanban Phase C). Managing assignments
 * is gated by production.supervisor.assign; reading "my sections" only needs
 * production.view. Branch isolation is automatic via the model global scope.
 */
final class StageSupervisorController extends BaseApiController
{
    public function __construct(private readonly StageSupervisorService $supervisors) {}

    /** All section-supervisor assignments in the active branch (management view). */
    public function index(): JsonResponse
    {
        $this->authorize('assignSupervisor', OrderItem::class);

        return $this->respond(
            StageSupervisorResource::collection($this->supervisors->listForBranch())->resolve(),
        );
    }

    public function store(AssignSupervisorRequest $request): JsonResponse
    {
        $this->authorize('assignSupervisor', OrderItem::class);

        $assignment = $this->supervisors->assign(
            (int) $request->integer('user_id'),
            (string) $request->string('stage'),
        );
        $assignment->load('user:id,name');

        return $this->respond((new StageSupervisorResource($assignment))->resolve(), 'Supervisor assigned', 201);
    }

    public function destroy(ProductionStageSupervisor $supervisor): JsonResponse
    {
        $this->authorize('assignSupervisor', OrderItem::class);

        $this->supervisors->unassign($supervisor);

        return $this->respond(null, 'Supervisor unassigned');
    }

    /** The stages the current user supervises in the active branch. */
    public function mySections(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OrderItem::class);

        /** @var User $actor */
        $actor = $request->user();

        return $this->respond(['stages' => $this->supervisors->stagesForUser($actor->id)]);
    }
}
