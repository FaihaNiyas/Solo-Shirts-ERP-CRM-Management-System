<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Order\Models\OrderItem;
use Illuminate\Support\Collection;

/**
 * Derives a rich, display-ready fulfillment summary for a Main Order purely from
 * its OrderItems' production states — the items remain the single source of
 * truth and nothing is ever stored on the order. Unlike OrderStatusDeriver
 * (which returns a coarse scalar kept for backward compatibility), this exposes
 * "partially_ready" / "partially_delivered" plus per-group counts and a
 * human-readable summary label.
 *
 * This is production status only. Lifecycle status (intake_preparation /
 * order_received) and payment status are computed elsewhere and stay distinct.
 */
final class OrderProgressSummary
{
    public const DRAFT = 'draft';
    public const IN_PRODUCTION = 'in_production';
    public const PARTIALLY_READY = 'partially_ready';
    public const READY = 'ready';
    public const PARTIALLY_DELIVERED = 'partially_delivered';
    public const DELIVERED = 'delivered';
    public const CANCELLED = 'cancelled';

    /** Aggregate-status → short display label. */
    private const AGGREGATE_LABELS = [
        self::DRAFT => 'Draft',
        self::IN_PRODUCTION => 'In Production',
        self::PARTIALLY_READY => 'Partially Ready',
        self::READY => 'Ready for Pickup',
        self::PARTIALLY_DELIVERED => 'Partially Delivered',
        self::DELIVERED => 'Delivered',
        self::CANCELLED => 'Cancelled',
    ];

    /**
     * Canonical, human-readable label for a single production state. This is the
     * one backend source of truth for stage labels (mirrored on the frontend).
     */
    public const STATE_LABELS = [
        OrderItem::STATE_DRAFT => 'Draft',
        OrderItem::STATE_FABRIC_ALLOCATED => 'Fabric Ready',
        OrderItem::STATE_CUTTING => 'Cutting',
        OrderItem::STATE_TAILORING => 'Tailoring',
        OrderItem::STATE_KAJA_BUTTON => 'Kaja / Button',
        OrderItem::STATE_FINISHING => 'Finishing',
        OrderItem::STATE_QC => 'QC',
        OrderItem::STATE_REWORK => 'Rework',
        OrderItem::STATE_PACKING => 'Packing',
        OrderItem::STATE_READY_FOR_DELIVERY => 'Ready for Pickup',
        OrderItem::STATE_DELIVERED => 'Delivered',
        OrderItem::STATE_CANCELLED => 'Cancelled',
    ];

    /**
     * States that count as "in production" for the progress rollup. Per the
     * product spec this group INCLUDES fabric_allocated (work has been committed),
     * which is wider than OrderItem::IN_PRODUCTION_STATES.
     *
     * @var list<string>
     */
    private const IN_PRODUCTION_GROUP = [
        OrderItem::STATE_FABRIC_ALLOCATED,
        OrderItem::STATE_CUTTING,
        OrderItem::STATE_TAILORING,
        OrderItem::STATE_KAJA_BUTTON,
        OrderItem::STATE_FINISHING,
        OrderItem::STATE_QC,
        OrderItem::STATE_REWORK,
        OrderItem::STATE_PACKING,
    ];

    public static function label(?string $state): ?string
    {
        if ($state === null) {
            return null;
        }

        return self::STATE_LABELS[$state] ?? ucwords(str_replace('_', ' ', $state));
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     * @return array{
     *   aggregate_status: string,
     *   aggregate_status_label: string,
     *   progress: array{total:int, draft:int, in_production:int, ready:int, delivered:int, cancelled:int, active:int},
     *   summary_label: string
     * }
     */
    public function summarise(Collection $items): array
    {
        $states = $items->map(fn (OrderItem $item): string => (string) $item->state);

        $total = $states->count();
        $draft = $states->filter(fn (string $s): bool => $s === OrderItem::STATE_DRAFT)->count();
        $inProduction = $states->filter(fn (string $s): bool => in_array($s, self::IN_PRODUCTION_GROUP, true))->count();
        $ready = $states->filter(fn (string $s): bool => $s === OrderItem::STATE_READY_FOR_DELIVERY)->count();
        $delivered = $states->filter(fn (string $s): bool => $s === OrderItem::STATE_DELIVERED)->count();
        $cancelled = $states->filter(fn (string $s): bool => $s === OrderItem::STATE_CANCELLED)->count();

        // The denominator for "X of N" excludes cancelled items — they are not
        // part of fulfillment. The cancelled count is still surfaced separately.
        $active = $total - $cancelled;

        $status = $this->deriveStatus($active, $draft, $inProduction, $ready, $delivered);

        return [
            'aggregate_status' => $status,
            'aggregate_status_label' => self::AGGREGATE_LABELS[$status],
            'progress' => [
                'total' => $total,
                'draft' => $draft,
                'in_production' => $inProduction,
                'ready' => $ready,
                'delivered' => $delivered,
                'cancelled' => $cancelled,
                'active' => $active,
            ],
            'summary_label' => $this->summaryLabel($status, $active, $inProduction, $ready, $delivered),
        ];
    }

    private function deriveStatus(int $active, int $draft, int $inProduction, int $ready, int $delivered): string
    {
        if ($active === 0) {
            return self::CANCELLED;                         // all items cancelled (or none)
        }
        if ($delivered === $active) {
            return self::DELIVERED;                         // every active item delivered
        }
        if ($delivered >= 1) {
            return self::PARTIALLY_DELIVERED;               // some delivered, some not
        }
        if ($ready === $active) {
            return self::READY;                             // no delivered, all active ready
        }
        if ($ready >= 1) {
            return self::PARTIALLY_READY;                   // some ready, some still not
        }
        if ($inProduction >= 1) {
            return self::IN_PRODUCTION;                     // work in progress, none ready
        }

        return self::DRAFT;                                 // all remaining are draft
    }

    private function summaryLabel(string $status, int $active, int $inProduction, int $ready, int $delivered): string
    {
        return match ($status) {
            self::DELIVERED => "Delivered — {$delivered} of {$active} items delivered",
            self::PARTIALLY_DELIVERED => "Partially Delivered — {$delivered} of {$active} items delivered",
            self::READY => "Ready for Pickup — {$ready} of {$active} items ready",
            self::PARTIALLY_READY => "Partially Ready — {$ready} of {$active} items ready",
            self::IN_PRODUCTION => "In Production — {$inProduction} of {$active} items in progress",
            self::CANCELLED => 'Cancelled',
            default => 'Draft',
        };
    }
}
