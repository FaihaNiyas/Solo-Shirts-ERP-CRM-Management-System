<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Production\Http\Resources\CutBundleResource;
use App\Modules\Production\Models\CutBundle;
use Illuminate\Http\JsonResponse;

final class BundleController extends BaseApiController
{
    public function show(CutBundle $bundle): JsonResponse
    {
        $this->authorize('view', $bundle);

        return $this->respond((new CutBundleResource($bundle))->resolve());
    }
}
