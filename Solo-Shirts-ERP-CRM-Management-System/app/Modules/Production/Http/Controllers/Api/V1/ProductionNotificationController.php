<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Production\Http\Resources\ProductionNotificationResource;
use App\Modules\Production\Models\ProductionNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The current user's in-app production notification feed (Kanban Phase F).
 * Notifications are personal — every query is scoped to the authenticated user
 * (branch isolation is automatic via the model global scope).
 */
final class ProductionNotificationController extends BaseApiController
{
    private const FEED_LIMIT = 50;

    public function index(Request $request): JsonResponse
    {
        $userId = $this->userId($request);

        $items = ProductionNotification::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit(self::FEED_LIMIT)
            ->get();

        $unread = ProductionNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return $this->respond([
            'unread_count' => $unread,
            'items' => ProductionNotificationResource::collection($items)->resolve(),
        ]);
    }

    public function read(Request $request, ProductionNotification $notification): JsonResponse
    {
        // A user may only read their own notifications.
        abort_unless($notification->user_id === $this->userId($request), 404);

        if (!$notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        return $this->respond((new ProductionNotificationResource($notification))->resolve(), 'Marked read');
    }

    public function readAll(Request $request): JsonResponse
    {
        ProductionNotification::query()
            ->where('user_id', $this->userId($request))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->respond(null, 'All notifications marked read');
    }

    private function userId(Request $request): int
    {
        /** @var User $actor */
        $actor = $request->user();

        return $actor->id;
    }
}
