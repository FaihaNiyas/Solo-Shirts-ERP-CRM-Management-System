<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Production\Http\Requests\AssignBundleRequest;
use App\Modules\Production\Http\Requests\CompleteAssignmentRequest;
use App\Modules\Production\Http\Requests\ReassignRequest;
use App\Modules\Production\Http\Requests\StartAssignmentRequest;
use App\Modules\Production\Http\Resources\AssignmentListResource;
use App\Modules\Production\Http\Resources\AssignmentResource;
use App\Modules\Production\Models\TailorAssignment;
use App\Modules\Production\Services\TailorAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TailorAssignmentController extends BaseApiController
{
    public function __construct(private readonly TailorAssignmentService $assignments) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TailorAssignment::class);

        $query = TailorAssignment::query()->latest('id');

        if ($request->filled('tailor')) {
            $query->where('tailor_id', $request->integer('tailor'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        return $this->respond(AssignmentListResource::collection($query->paginate(20)->items())->resolve());
    }

    public function store(AssignBundleRequest $request): JsonResponse
    {
        $this->authorize('create', TailorAssignment::class);

        /** @var User $actor */
        $actor = $request->user();
        $notes = $request->filled('notes') ? (string) $request->string('notes') : null;

        $assignment = $this->assignments->assign(
            (int) $request->integer('bundle_id'),
            (int) $request->integer('tailor_id'),
            $notes,
            $actor,
        );

        return $this->respond((new AssignmentResource($assignment))->resolve(), 'Bundle assigned', 201);
    }

    public function start(StartAssignmentRequest $request, TailorAssignment $assignment): JsonResponse
    {
        $this->authorize('start', $assignment);

        $assignment = $this->assignments->start($assignment);

        return $this->respond((new AssignmentResource($assignment))->resolve(), 'Assignment started');
    }

    public function complete(CompleteAssignmentRequest $request, TailorAssignment $assignment): JsonResponse
    {
        $this->authorize('complete', $assignment);

        /** @var User $actor */
        $actor = $request->user();
        $assignment = $this->assignments->complete($assignment, $actor);

        return $this->respond((new AssignmentResource($assignment))->resolve(), 'Assignment completed');
    }

    public function reassign(ReassignRequest $request, TailorAssignment $assignment): JsonResponse
    {
        $this->authorize('reassign', $assignment);

        /** @var User $actor */
        $actor = $request->user();
        $notes = $request->filled('notes') ? (string) $request->string('notes') : null;

        $new = $this->assignments->reassign($assignment, (int) $request->integer('tailor_id'), $notes, $actor);

        return $this->respond((new AssignmentResource($new))->resolve(), 'Assignment reassigned', 201);
    }
}
