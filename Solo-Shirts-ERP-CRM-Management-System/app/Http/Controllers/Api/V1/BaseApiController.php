<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

/**
 * Base controller for every v1 API endpoint. Provides policy authorization and
 * a single helper that always emits the standard success envelope, so
 * controllers never hand-build response shapes.
 */
abstract class BaseApiController extends Controller
{
    use AuthorizesRequests;

    protected function respond(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    /**
     * Standard paginated envelope: `data` rows plus `meta` the SPA reads to drive
     * its page controls. Pass the already-resolved resource rows as $rows so the
     * caller controls the row shape (e.g. a list resource).
     *
     * @param  LengthAwarePaginator<int, mixed>  $page
     * @param  array<int, mixed>  $rows
     */
    protected function respondPaginated(LengthAwarePaginator $page, array $rows, string $message = 'OK'): JsonResponse
    {
        return $this->respond([
            'data' => $rows,
            'meta' => [
                'total' => $page->total(),
                'per_page' => $page->perPage(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
            ],
        ], $message);
    }
}
