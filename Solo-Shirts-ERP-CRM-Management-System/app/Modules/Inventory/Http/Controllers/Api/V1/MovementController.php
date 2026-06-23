<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Inventory\Http\Resources\MovementResource;
use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MovementController extends BaseApiController
{
    public function index(Request $request, BranchContext $branch): JsonResponse
    {
        $this->authorize('view', FabricRoll::class);

        $query = FabricMovement::query()->latest('id');

        // fabric_movements has no global branch scope; isolate explicitly.
        $branchId = $branch->current();
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('roll_id')) {
            $query->where('fabric_roll_id', $request->integer('roll_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('occurred_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('occurred_at', '<=', $request->date('to'));
        }

        return $this->respond(MovementResource::collection($query->paginate(50)->items())->resolve());
    }
}
