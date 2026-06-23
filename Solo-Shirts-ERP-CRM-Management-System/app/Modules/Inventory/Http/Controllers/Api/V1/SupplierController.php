<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Inventory\Http\Requests\CreateSupplierRequest;
use App\Modules\Inventory\Http\Requests\UpdateSupplierRequest;
use App\Modules\Inventory\Http\Resources\SupplierResource;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Models\Supplier;
use App\Modules\Inventory\Services\SupplierService;
use Illuminate\Http\JsonResponse;

final class SupplierController extends BaseApiController
{
    public function __construct(private readonly SupplierService $suppliers) {}

    public function index(): JsonResponse
    {
        $this->authorize('view', FabricRoll::class);

        return $this->respond(SupplierResource::collection(Supplier::query()->orderBy('name')->get())->resolve());
    }

    public function store(CreateSupplierRequest $request): JsonResponse
    {
        $this->authorize('manageSuppliers', FabricRoll::class);

        /** @var User $actor */
        $actor = $request->user();
        $supplier = $this->suppliers->create($request->validated(), $actor);

        return $this->respond((new SupplierResource($supplier))->resolve(), 'Supplier created', 201);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('manageSuppliers', FabricRoll::class);

        $supplier = $this->suppliers->update($supplier, $request->validated());

        return $this->respond((new SupplierResource($supplier))->resolve(), 'Supplier updated');
    }
}
