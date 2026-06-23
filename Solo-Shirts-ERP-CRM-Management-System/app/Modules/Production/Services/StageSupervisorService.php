<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Modules\Production\Models\ProductionStageSupervisor;
use Illuminate\Database\Eloquent\Collection;

/**
 * Manages production section-supervisor assignments and the read models the board
 * needs from them. Branch isolation is automatic — the ProductionStageSupervisor
 * global scope restricts every query to the caller's active branch.
 */
final class StageSupervisorService
{
    /**
     * Idempotent: assigning the same (user, stage) twice returns the existing row.
     * The read is branch-scoped by the global scope and branch_id is auto-stamped
     * from the active branch context on create.
     */
    public function assign(int $userId, string $stage): ProductionStageSupervisor
    {
        return ProductionStageSupervisor::query()->firstOrCreate([
            'user_id' => $userId,
            'stage' => $stage,
        ]);
    }

    public function unassign(ProductionStageSupervisor $assignment): void
    {
        $assignment->delete();
    }

    /**
     * All assignments in the active branch, supervisor loaded, for the management view.
     *
     * @return Collection<int, ProductionStageSupervisor>
     */
    public function listForBranch(): Collection
    {
        return ProductionStageSupervisor::query()
            ->with('user:id,name')
            ->orderBy('stage')
            ->get();
    }

    /**
     * The stages a user supervises in the active branch (drives "my section").
     *
     * @return list<string>
     */
    public function stagesForUser(int $userId): array
    {
        return ProductionStageSupervisor::query()
            ->where('user_id', $userId)
            ->pluck('stage')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Map of stage => supervisor names for the active branch, for card display.
     *
     * @return array<string, list<string>>
     */
    public function namesByStage(): array
    {
        $map = [];

        foreach (ProductionStageSupervisor::query()->with('user:id,name')->get() as $row) {
            $name = $row->user?->name;
            if ($name !== null) {
                $map[$row->stage][] = $name;
            }
        }

        return $map;
    }

    /**
     * Supervisor names for one stage in the active branch.
     *
     * @return list<string>
     */
    public function namesForStage(string $stage): array
    {
        return ProductionStageSupervisor::query()
            ->where('stage', $stage)
            ->with('user:id,name')
            ->get()
            ->map(fn (ProductionStageSupervisor $s): ?string => $s->user?->name)
            ->filter()
            ->values()
            ->all();
    }
}
