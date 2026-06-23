<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Inventory\Http\Requests\FabricTypeRequest;
use App\Modules\Inventory\Http\Resources\FabricTypeResource;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Models\FabricType;
use Illuminate\Http\JsonResponse;

final class FabricTypeController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('view', FabricRoll::class);

        return $this->respond(FabricTypeResource::collection(FabricType::query()->orderBy('name')->get())->resolve());
    }

    public function store(FabricTypeRequest $request): JsonResponse
    {
        $this->authorize('manageFabricTypes', FabricRoll::class);

        $type = FabricType::query()->create($request->validated());

        return $this->respond((new FabricTypeResource($type))->resolve(), 'Fabric type created', 201);
    }

    public function update(FabricTypeRequest $request, FabricType $fabricType): JsonResponse
    {
        $this->authorize('manageFabricTypes', FabricRoll::class);

        $fabricType->update($request->validated());

        return $this->respond((new FabricTypeResource($fabricType))->resolve(), 'Fabric type updated');
    }
}
