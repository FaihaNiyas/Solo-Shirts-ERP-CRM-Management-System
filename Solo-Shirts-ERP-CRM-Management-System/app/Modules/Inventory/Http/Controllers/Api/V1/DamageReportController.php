<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Inventory\Http\Requests\CreateDamageReportRequest;
use App\Modules\Inventory\Http\Resources\DamageReportListResource;
use App\Modules\Inventory\Http\Resources\DamageReportResource;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Services\DamageReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DamageReportController extends BaseApiController
{
    public function __construct(private readonly DamageReportService $reports) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DamageReport::class);

        $query = DamageReport::query()->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }
        if ($request->filled('stage')) {
            $query->where('stage', (string) $request->string('stage'));
        }
        if ($request->filled('damage_type')) {
            $query->where('damage_type', (string) $request->string('damage_type'));
        }
        if ($request->filled('order_item_id')) {
            $query->where('order_item_id', $request->integer('order_item_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('reported_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('reported_at', '<=', $request->date('to'));
        }

        return $this->respond(DamageReportListResource::collection($query->paginate(20)->items())->resolve());
    }

    public function store(CreateDamageReportRequest $request): JsonResponse
    {
        $this->authorize('create', DamageReport::class);

        /** @var User $actor */
        $actor = $request->user();
        $report = $this->reports->report($request->validated(), $actor);

        return $this->respond((new DamageReportResource($report))->resolve(), 'Damage report created', 201);
    }

    public function show(DamageReport $damageReport): JsonResponse
    {
        $this->authorize('view', DamageReport::class);

        return $this->respond((new DamageReportResource($damageReport->load('photos')))->resolve());
    }
}
