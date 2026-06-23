<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin FabricMovement
 */
final class MovementResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fabric_roll_id' => $this->fabric_roll_id,
            'type' => $this->type,
            'metres' => $this->metres,
            'direction' => $this->direction(),
            'reason' => $this->reason,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'occurred_at' => $this->date($this->occurred_at),
        ];
    }

    private function direction(): string
    {
        return match (true) {
            in_array($this->type, FabricMovement::ADDITIONS, true) => 'in',
            in_array($this->type, FabricMovement::DEDUCTIONS, true) => 'out',
            default => 'hold',
        };
    }
}
