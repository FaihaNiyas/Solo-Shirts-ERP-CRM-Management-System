<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Delivery\Http\Requests\RecordAttemptRequest;
use App\Modules\Delivery\Http\Resources\DeliveryAttemptResource;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Services\DeliveryService;
use Illuminate\Http\JsonResponse;

final class DeliveryAttemptController extends BaseApiController
{
    public function __construct(private readonly DeliveryService $deliveries) {}

    public function store(RecordAttemptRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorize('attempt', Delivery::class);

        /** @var User $actor */
        $actor = $request->user();
        $notes = $request->filled('notes') ? (string) $request->string('notes') : null;

        $attempt = $this->deliveries->attempt(
            $delivery,
            (string) $request->string('reason_code'),
            $notes,
            $actor,
        );

        return $this->respond((new DeliveryAttemptResource($attempt))->resolve(), 'Attempt recorded', 201);
    }
}
