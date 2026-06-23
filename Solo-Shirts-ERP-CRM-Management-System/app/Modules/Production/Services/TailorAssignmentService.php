<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Exceptions\TailoringException;
use App\Modules\Production\Models\CutBundle;
use App\Modules\Production\Models\TailorAssignment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Assigns cut bundles to tailors and walks an assignment through its lifecycle.
 * The single-active-assignment-per-bundle rule is enforced by the DB (partial
 * unique emulation); a violation surfaces as DUPLICATE_ACTIVE_ASSIGNMENT.
 */
final class TailorAssignmentService
{
    public function __construct(private readonly StateTransitionService $transitions) {}

    public function assign(int $bundleId, int $tailorId, ?string $notes, User $actor): TailorAssignment
    {
        $bundle = CutBundle::query()->find($bundleId);

        if ($bundle === null) {
            throw TailoringException::invalidBundle();
        }

        $tailor = $this->resolveTailor($tailorId, $bundle->branch_id);

        try {
            return TailorAssignment::query()->create([
                'bundle_id' => $bundle->id,
                'order_item_id' => $bundle->order_item_id,
                'branch_id' => $bundle->branch_id,
                'tailor_id' => $tailor->id,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'status' => TailorAssignment::STATUS_ASSIGNED,
                'notes' => $notes,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw TailoringException::duplicateActiveAssignment();
        }
    }

    public function start(TailorAssignment $assignment): TailorAssignment
    {
        if ($assignment->status !== TailorAssignment::STATUS_ASSIGNED) {
            throw TailoringException::invalidState();
        }

        $assignment->update([
            'status' => TailorAssignment::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        return $assignment;
    }

    public function complete(TailorAssignment $assignment, User $actor): TailorAssignment
    {
        if ($assignment->status !== TailorAssignment::STATUS_IN_PROGRESS) {
            throw TailoringException::invalidState();
        }

        return DB::transaction(function () use ($assignment, $actor): TailorAssignment {
            $item = OrderItem::query()->findOrFail($assignment->order_item_id);
            $state = (string) $item->state;

            if ($state === OrderItem::STATE_CANCELLED) {
                throw TailoringException::itemCancelled();
            }

            $assignment->update([
                'status' => TailorAssignment::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            // Advance the item out of tailoring (skip if another bundle already did).
            if ($state === OrderItem::STATE_TAILORING) {
                $this->transitions->transition(
                    $item->id,
                    OrderItem::STATE_KAJA_BUTTON,
                    $actor,
                    (string) Str::uuid(),
                    'tailoring completed',
                );
            }

            return $assignment;
        });
    }

    public function reassign(TailorAssignment $assignment, int $newTailorId, ?string $notes, User $actor): TailorAssignment
    {
        if ($assignment->status !== TailorAssignment::STATUS_ASSIGNED) {
            throw TailoringException::alreadyStarted();
        }

        $tailor = $this->resolveTailor($newTailorId, $assignment->branch_id);

        return DB::transaction(function () use ($assignment, $tailor, $notes, $actor): TailorAssignment {
            // Free the partial-unique slot before inserting the replacement.
            $assignment->update(['status' => TailorAssignment::STATUS_REASSIGNED]);

            $new = TailorAssignment::query()->create([
                'bundle_id' => $assignment->bundle_id,
                'order_item_id' => $assignment->order_item_id,
                'branch_id' => $assignment->branch_id,
                'tailor_id' => $tailor->id,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'status' => TailorAssignment::STATUS_ASSIGNED,
                'notes' => $notes,
            ]);

            activity('tailoring')
                ->performedOn($new)
                ->withProperties([
                    'from_assignment_id' => $assignment->id,
                    'from_tailor_id' => $assignment->tailor_id,
                    'to_tailor_id' => $tailor->id,
                ])
                ->event('reassigned')
                ->log("bundle {$assignment->bundle_id} reassigned to tailor {$tailor->id}");

            return $new;
        });
    }

    private function resolveTailor(int $tailorId, int $branchId): User
    {
        /** @var User|null $tailor */
        $tailor = User::query()->find($tailorId);

        if ($tailor === null || $tailor->branch_id !== $branchId) {
            throw TailoringException::invalidTailor();
        }

        if (!$tailor->is_active) {
            throw TailoringException::tailorInactive();
        }

        return $tailor;
    }
}
