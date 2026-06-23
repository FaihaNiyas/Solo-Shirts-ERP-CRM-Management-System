<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Inventory\Http\Requests\CreatePoRequest;
use App\Modules\Inventory\Http\Requests\ReceivePoRequest;
use App\Modules\Inventory\Http\Resources\PoResource;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PurchaseOrderController extends BaseApiController
{
    public function __construct(private readonly PurchaseOrderService $purchaseOrders) {}

    public function index(): JsonResponse
    {
        $this->authorize('view', FabricRoll::class);

        $orders = PurchaseOrder::query()->with('supplier', 'items')->latest('id')->paginate(20)->items();

        return $this->respond(PoResource::collection($orders)->resolve());
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('view', FabricRoll::class);

        return $this->respond((new PoResource($purchaseOrder->load('supplier', 'items')))->resolve());
    }

    public function store(CreatePoRequest $request): JsonResponse
    {
        $this->authorize('createPo', FabricRoll::class);

        /** @var User $actor */
        $actor = $request->user();
        $po = $this->purchaseOrders->draft($request->validated(), $actor);

        return $this->respond((new PoResource($po))->resolve(), 'Purchase order drafted', 201);
    }

    public function place(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('placePo', FabricRoll::class);

        $po = $this->purchaseOrders->place($purchaseOrder);

        return $this->respond((new PoResource($po->load('items')))->resolve(), 'Purchase order placed');
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('placePo', FabricRoll::class);

        $po = $this->purchaseOrders->cancel($purchaseOrder);

        return $this->respond((new PoResource($po->load('items')))->resolve(), 'Purchase order cancelled');
    }

    public function receive(ReceivePoRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('receivePo', FabricRoll::class);

        /** @var User $actor */
        $actor = $request->user();
        $grn = $this->purchaseOrders->receive($purchaseOrder, $request->validated(), $actor);

        return $this->respond([
            'grn_id' => $grn->id,
            'purchase_order' => (new PoResource($purchaseOrder->refresh()->load('items')))->resolve(),
        ], 'Goods received', 201);
    }
}
