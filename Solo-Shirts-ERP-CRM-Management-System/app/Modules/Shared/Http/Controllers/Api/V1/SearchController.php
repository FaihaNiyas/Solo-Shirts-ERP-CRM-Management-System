<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Shared\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController extends BaseApiController
{
    public function __construct(private readonly GlobalSearchService $search) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $results = $this->search->search((string) $request->query('q', ''), $actor);

        return $this->respond(['results' => $results]);
    }
}
