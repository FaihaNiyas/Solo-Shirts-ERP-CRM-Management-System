<?php

declare(strict_types=1);

namespace App\Modules\Production\States;

use App\Modules\Order\Models\OrderItem;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * The production workflow state machine for an order_item. The allowed edges are
 * the single source of truth; the StateTransitionService validates every move
 * against them. Cancellation edges are permitted by the machine but gated by
 * permission (post-cutting cancels need supervisor approval).
 *
 * @extends State<OrderItem>
 */
abstract class ProductionState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->registerState([
                Draft::class,
                FabricAllocated::class,
                Cutting::class,
                Tailoring::class,
                KajaButton::class,
                Finishing::class,
                Qc::class,
                Rework::class,
                Packing::class,
                ReadyForDelivery::class,
                Delivered::class,
                Cancelled::class,
            ])
            // Forward production flow.
            ->allowTransition(Draft::class, FabricAllocated::class)
            ->allowTransition(FabricAllocated::class, Cutting::class)
            ->allowTransition(Cutting::class, Tailoring::class)
            ->allowTransition(Tailoring::class, KajaButton::class)
            ->allowTransition(KajaButton::class, Finishing::class)
            ->allowTransition(Finishing::class, Qc::class)
            ->allowTransition(Qc::class, Packing::class)
            ->allowTransition(Packing::class, ReadyForDelivery::class)
            ->allowTransition(ReadyForDelivery::class, Delivered::class)
            // QC rework loop. A QC fail parks the item in Rework; from there it can
            // be re-inspected directly (back to Qc) or routed back to the stage that
            // owns the fix (cutting / tailoring / kaja_button / finishing) so the
            // garment re-flows forward through QC again. This is internal production
            // rework only — never a post-delivery customer alteration.
            ->allowTransition(Qc::class, Rework::class)
            ->allowTransition(Rework::class, Qc::class)
            ->allowTransition(Rework::class, Cutting::class)
            ->allowTransition(Rework::class, Tailoring::class)
            ->allowTransition(Rework::class, KajaButton::class)
            ->allowTransition(Rework::class, Finishing::class)
            // Cancellation: allowed up to (and including) Packing; terminal after
            // delivery. Who may cancel post-cutting is enforced by permission.
            ->allowTransition([
                Draft::class,
                FabricAllocated::class,
                Cutting::class,
                Tailoring::class,
                KajaButton::class,
                Finishing::class,
                Qc::class,
                Rework::class,
                Packing::class,
            ], Cancelled::class);
    }
}
