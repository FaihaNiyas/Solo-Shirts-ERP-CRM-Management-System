<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Reporting\Http\Resources\NotificationResource;
use App\Modules\Reporting\Models\NotificationMessage;
use App\Modules\Reporting\Models\ReportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewNotifications', ReportJob::class);

        $query = NotificationMessage::query()->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', (string) $request->string('channel'));
        }

        return $this->respond(NotificationResource::collection($query->get())->resolve());
    }
}
