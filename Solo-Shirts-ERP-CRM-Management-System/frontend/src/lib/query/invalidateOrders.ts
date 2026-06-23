import type { QueryClient } from '@tanstack/react-query'

/**
 * Any production / cutting / tailoring / QC / packing / rack / delivery /
 * handover action that changes an order item's state recomputes the parent
 * order's aggregate progress (order.progress). The order list + detail pages
 * read that progress, so their caches must be invalidated too — otherwise the
 * status only updates after a manual page refresh.
 *
 * We invalidate by prefix:
 *   ['orders']             → orders list, order detail, order items, etc.
 *   ['production','orders'] → the board's per-order production summaries.
 *
 * Prefix-matching means callers don't need the specific orderId, and
 * invalidateQueries only refetches *active* (mounted) queries — so this is cheap
 * when the user is sitting on the production board with no order query mounted.
 */
export function invalidateOrderCaches(qc: QueryClient): void {
  qc.invalidateQueries({ queryKey: ['orders'] })
  qc.invalidateQueries({ queryKey: ['production', 'orders'] })
}
