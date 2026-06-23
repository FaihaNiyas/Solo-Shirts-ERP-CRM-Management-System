<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Delivery\Http\Requests\CancelDeliveryRequest;
use App\Modules\Delivery\Http\Requests\CreateDeliveryRequest;
use App\Modules\Delivery\Http\Requests\DispatchRequest;
use App\Modules\Delivery\Http\Resources\DeliveryResource;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeliveryController extends BaseApiController
{
    public function __construct(private readonly DeliveryService $deliveries) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Delivery::class);

        $query = Delivery::query()->with('order.items')->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to'));
        }

        $perPage = max(1, min(100, $request->integer('per_page', 20)));
        $page = $query->paginate($perPage);

        return $this->respondPaginated($page, DeliveryResource::collection($page->items())->resolve());
    }

    public function store(CreateDeliveryRequest $request): JsonResponse
    {
        $this->authorize('create', Delivery::class);

        /** @var User $actor */
        $actor = $request->user();

        $delivery = $this->deliveries->create($request->validated(), $actor);

        return $this->respond((new DeliveryResource($delivery->loadMissing('order.items')))->resolve(), 'Delivery created', 201);
    }

    public function dispatchDelivery(DispatchRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorize('dispatch', Delivery::class);

        $delivery = $this->deliveries->dispatch($delivery);

        return $this->respond((new DeliveryResource($delivery->loadMissing('order.items')))->resolve(), 'OTP dispatched');
    }

    public function cancel(CancelDeliveryRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorize('cancel', Delivery::class);

        /** @var User $actor */
        $actor = $request->user();
        $reason = $request->filled('reason') ? (string) $request->string('reason') : null;

        $delivery = $this->deliveries->cancel($delivery, $reason, $actor);

        return $this->respond((new DeliveryResource($delivery->loadMissing('order.items')))->resolve(), 'Delivery cancelled');
    }
}
