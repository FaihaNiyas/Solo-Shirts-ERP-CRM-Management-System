<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Inventory\Http\Resources\LowStockResource;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Services\LowStockService;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Http\JsonResponse;

final class LowStockController extends BaseApiController
{
    public function __construct(private readonly LowStockService $lowStock) {}

    public function index(BranchContext $branch): JsonResponse
    {
        $this->authorize('viewLowStock', FabricRoll::class);

        $rows = $this->lowStock->lowStock($branch->current());

        return $this->respond(LowStockResource::collection($rows)->resolve());
    }
}
