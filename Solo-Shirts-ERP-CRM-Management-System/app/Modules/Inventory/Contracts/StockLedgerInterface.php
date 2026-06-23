<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Contracts;

use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;

/**
 * The fabric stock ledger seam. Other modules (Production/cutting) depend on this
 * contract and never touch fabric_movements directly. Phase 11 owns the
 * implementation; Phase 8 reserves/consumes through it.
 */
interface StockLedgerInterface
{
    /**
     * Metres that can still be reserved = remaining_metres − net active reserves.
     */
    public function availableMetres(FabricRoll $roll): float;

    /**
     * The roll's stock position in one pass: remaining, available, currently
     * reserved (net active holds), total consumed (out) and total damaged
     * (write-off).
     *
     * @return array{remaining: float, available: float, reserved: float, consumed: float, damaged: float}
     */
    public function breakdown(FabricRoll $roll): array;

    /**
     * Append one ledger movement and update the roll's cached remaining_metres in
     * the same locked transaction. `metres` is a positive magnitude; direction is
     * derived from `type`.
     *
     * @param  array{type?: string, id?: int}  $reference
     */
    public function record(
        int $rollId,
        string $type,
        float $metres,
        ?string $reason,
        array $reference,
        ?int $actorId,
        ?string $idempotencyKey = null,
    ): FabricMovement;

    /**
     * Record a soft hold on stock (does not change remaining_metres).
     */
    public function recordReserve(FabricRoll $roll, int $orderItemId, float $metres, ?int $actorId, ?string $idempotencyKey = null): FabricMovement;

    /**
     * Release a previously held reserve, returning it to available.
     */
    public function recordRelease(FabricRoll $roll, int $orderItemId, float $metres, ?int $actorId, ?string $reason = null): FabricMovement;

    /**
     * Physically consume metres: reduces remaining_metres and closes the reserve.
     */
    public function recordConsume(FabricRoll $roll, int $orderItemId, float $metres, ?int $actorId): FabricMovement;
}
