<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\ProductionBox;
use Illuminate\Support\Facades\DB;

/**
 * Assigns exactly one production box to one sub-order. Auto mode picks the
 * lowest free box (creating the next sequential BOX-### when the pool is empty);
 * manual mode targets a specific code, rejecting it if another active sub-order
 * already holds it. All mutation happens inside a row-locked transaction so two
 * concurrent assignments can't grab the same box.
 */
final class BoxAssignmentService
{
    public const MODE_AUTO = 'auto';

    public const MODE_MANUAL = 'manual';

    public function assign(OrderItem $item, string $mode, ?string $boxCode): ProductionBox
    {
        return DB::transaction(function () use ($item, $mode, $boxCode): ProductionBox {
            // Free any box this item currently holds (supports re-assignment).
            $this->releaseCurrent($item);

            $box = $mode === self::MODE_MANUAL
                ? $this->resolveManual($item, $boxCode)
                : $this->nextFreeOrCreate();

            $box->forceFill([
                'status' => ProductionBox::STATUS_OCCUPIED,
                'current_order_item_id' => $item->id,
                'assigned_at' => now(),
                'released_at' => null,
            ])->save();

            $item->forceFill([
                'production_box_id' => $box->id,
                'box_code' => $box->box_code,
            ])->save();

            return $box;
        });
    }

    public function releaseCurrent(OrderItem $item): void
    {
        if ($item->production_box_id === null) {
            return;
        }

        $current = ProductionBox::query()->lockForUpdate()->find($item->production_box_id);

        if ($current !== null) {
            $current->forceFill([
                'status' => ProductionBox::STATUS_FREE,
                'current_order_item_id' => null,
                'released_at' => now(),
            ])->save();
        }

        $item->forceFill([
            'production_box_id' => null,
            'box_code' => null,
            'placed_in_box' => false,
            'placed_in_box_at' => null,
            'placed_in_box_by' => null,
        ])->save();
    }

    private function resolveManual(OrderItem $item, ?string $boxCode): ProductionBox
    {
        $code = trim((string) $boxCode);

        if ($code === '') {
            throw OrderException::boxCodeRequired();
        }

        $box = ProductionBox::query()->lockForUpdate()->where('box_code', $code)->first();

        if ($box === null) {
            // A brand-new physical box being introduced — register it as free.
            $box = new ProductionBox(['box_code' => $code, 'status' => ProductionBox::STATUS_FREE]);
            $box->save();

            return $box;
        }

        if ($box->isOccupiedByOther($item->id)) {
            throw OrderException::boxOccupied($code, $box->currentItem?->item_code);
        }

        return $box;
    }

    private function nextFreeOrCreate(): ProductionBox
    {
        $free = ProductionBox::query()->lockForUpdate()
            ->where('status', ProductionBox::STATUS_FREE)
            ->whereNull('current_order_item_id')
            ->orderBy('id')
            ->first();

        if ($free !== null) {
            return $free;
        }

        // Pool exhausted — mint the next sequential code for this branch.
        $n = ProductionBox::query()->withTrashed()->count() + 1;

        do {
            $code = 'BOX-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            $n++;
        } while (ProductionBox::query()->withTrashed()->where('box_code', $code)->exists());

        $box = new ProductionBox(['box_code' => $code, 'status' => ProductionBox::STATUS_FREE]);
        $box->save();

        return $box;
    }
}
