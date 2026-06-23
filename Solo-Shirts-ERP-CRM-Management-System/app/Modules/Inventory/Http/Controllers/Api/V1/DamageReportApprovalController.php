<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Inventory\Http\Requests\ApproveDamageRequest;
use App\Modules\Inventory\Http\Requests\RejectDamageRequest;
use App\Modules\Inventory\Http\Resources\DamageReportResource;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Services\DamageReportService;
use Illuminate\Http\JsonResponse;

final class DamageReportApprovalController extends BaseApiController
{
    public function __construct(private readonly DamageReportService $reports) {}

    public function approve(ApproveDamageRequest $request, DamageReport $damageReport): JsonResponse
    {
        $this->authorize('approve', $damageReport);

        /** @var User $actor */
        $actor = $request->user();
        $key = (string) $request->header('Idempotency-Key');
        $notes = $request->filled('notes') ? (string) $request->string('notes') : null;

        $report = $this->reports->approve($damageReport, $notes, $actor, $key);

        return $this->respond((new DamageReportResource($report->load('photos')))->resolve(), 'Damage report approved');
    }

    public function reject(RejectDamageRequest $request, DamageReport $damageReport): JsonResponse
    {
        $this->authorize('reject', $damageReport);

        /** @var User $actor */
        $actor = $request->user();
        $report = $this->reports->reject($damageReport, (string) $request->string('reason'), $actor);

        return $this->respond((new DamageReportResource($report->load('photos')))->resolve(), 'Damage report rejected');
    }
}
