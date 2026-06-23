<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Production\Http\Requests\CreateDefectCategoryRequest;
use App\Modules\Production\Http\Resources\DefectCategoryResource;
use App\Modules\Production\Models\DefectCategory;
use App\Modules\Shared\Services\BranchContext;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class DefectCategoryController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', DefectCategory::class);

        $categories = DefectCategory::query()->orderBy('name')->get();

        return $this->respond(DefectCategoryResource::collection($categories)->resolve());
    }

    public function store(CreateDefectCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', DefectCategory::class);

        $category = DefectCategory::query()->create($request->validated());

        return $this->respond((new DefectCategoryResource($category))->resolve(), 'Defect category created', 201);
    }

    /**
     * Top defect categories over a recent window, branch-scoped.
     */
    public function analytics(Request $request, BranchContext $branch): JsonResponse
    {
        $this->authorize('viewAny', DefectCategory::class);

        $days = max(1, $request->integer('days', 30));
        $branchId = $branch->current();

        $rows = DB::table('qc_defects as d')
            ->join('qc_inspections as i', 'i.id', '=', 'd.qc_inspection_id')
            ->join('defect_categories as c', 'c.id', '=', 'd.defect_category_id')
            ->where('i.inspected_at', '>=', now()->subDays($days))
            ->when($branchId !== null, fn (Builder $q): Builder => $q->where('i.branch_id', $branchId))
            ->groupBy('c.id', 'c.code', 'c.name')
            ->selectRaw('c.id, c.code, c.name, COUNT(*) as defect_count')
            ->orderByDesc('defect_count')
            ->orderBy('c.id')
            ->limit(5)
            ->get();

        $data = $rows->map(fn (object $r): array => [
            'id' => (int) $r->id,
            'code' => (string) $r->code,
            'name' => (string) $r->name,
            'defect_count' => (int) $r->defect_count,
        ])->all();

        return $this->respond($data);
    }
}
