<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Delivery\Http\Requests\ConfirmDeliveryRequest;
use App\Modules\Delivery\Http\Resources\DeliveryResource;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Services\DeliveryService;
use Illuminate\Http\JsonResponse;

final class DeliveryConfirmationController extends BaseApiController
{
    public function __construct(private readonly DeliveryService $deliveries) {}

    public function confirm(ConfirmDeliveryRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorize('confirm', Delivery::class);

        /** @var User $actor */
        $actor = $request->user();

        $delivery = $this->deliveries->confirm($delivery, (string) $request->string('otp'), $actor);

        return $this->respond((new DeliveryResource($delivery->loadMissing('order.items')))->resolve(), 'Delivery confirmed');
    }
}
