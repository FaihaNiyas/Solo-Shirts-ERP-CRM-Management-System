<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Exceptions\InventoryException;
use App\Modules\Inventory\Models\Grn;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Purchase order lifecycle: draft → placed → (partial_received) → received, or
 * cancelled. Receiving is fully transactional: it mints a GRN, creates fabric
 * rolls with their receive movements (via FabricRollService), and advances PO
 * line/header receipt state.
 */
final class PurchaseOrderService
{
    public function __construct(private readonly FabricRollService $rolls) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function draft(array $data, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $actor): PurchaseOrder {
            /** @var list<array<string, mixed>> $items */
            $items = $data['items'];

            $total = 0;
            foreach ($items as $item) {
                $total += (int) round(((float) $item['quantity_metres']) * ((int) $item['unit_price_paise']));
            }

            $po = PurchaseOrder::query()->create([
                'branch_id' => $actor->branch_id,
                'po_code' => 'SSI-PO-' . strtoupper(Str::random(8)),
                'supplier_id' => $data['supplier_id'],
                'status' => PurchaseOrder::STATUS_DRAFT,
                'total_paise' => $total,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($items as $item) {
                $po->items()->create([
                    'fabric_type_id' => $item['fabric_type_id'],
                    'colour' => $item['colour'] ?? null,
                    'quantity_metres' => $item['quantity_metres'],
                    'unit_price_paise' => $item['unit_price_paise'],
                    'received_metres' => 0,
                ]);
            }

            return $po->load('items');
        });
    }

    public function place(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
            throw InventoryException::poNotDraft();
        }

        $po->update(['status' => PurchaseOrder::STATUS_PLACED, 'placed_at' => now()]);

        return $po;
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status === PurchaseOrder::STATUS_RECEIVED) {
            throw InventoryException::poAlreadyReceived();
        }

        $po->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        return $po;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function receive(PurchaseOrder $po, array $data, User $actor): Grn
    {
        if (!in_array($po->status, [PurchaseOrder::STATUS_PLACED, PurchaseOrder::STATUS_PARTIAL_RECEIVED], true)) {
            throw InventoryException::poNotPlaced();
        }

        return DB::transaction(function () use ($po, $data, $actor): Grn {
            $grn = Grn::query()->create([
                'purchase_order_id' => $po->id,
                'branch_id' => $po->branch_id,
                'received_at' => now(),
                'received_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
            ]);

            /** @var list<array<string, mixed>> $lines */
            $lines = $data['lines'];

            foreach ($lines as $line) {
                /** @var PurchaseOrderItem $poItem */
                $poItem = $po->items()->findOrFail($line['purchase_order_item_id']);
                $metres = (float) $line['metres'];

                $newReceived = (float) $poItem->received_metres + $metres;

                if ($newReceived > (float) $poItem->quantity_metres && !$actor->can('inventory.fabric_rolls.adjust_out_approve')) {
                    throw InventoryException::overReceiptRequiresApproval();
                }

                $roll = $this->rolls->create([
                    'fabric_type_id' => $poItem->fabric_type_id,
                    'colour' => $poItem->colour,
                    'supplier_id' => $po->supplier_id,
                    'received_length_metres' => $metres,
                    'unit_price_paise' => $poItem->unit_price_paise,
                    'rack_location' => $line['rack_location'] ?? null,
                ], $actor);

                $grn->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'fabric_roll_id' => $roll->id,
                    'metres_received' => $metres,
                ]);

                $poItem->update(['received_metres' => $newReceived]);
            }

            $po->load('items');
            $allReceived = $po->items->every(
                fn (PurchaseOrderItem $i): bool => (float) $i->received_metres >= (float) $i->quantity_metres,
            );

            $po->update([
                'status' => $allReceived ? PurchaseOrder::STATUS_RECEIVED : PurchaseOrder::STATUS_PARTIAL_RECEIVED,
            ]);

            return $grn->load('items');
        });
    }
}
