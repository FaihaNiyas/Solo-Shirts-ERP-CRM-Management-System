// Composes the user-facing order status from the item-derived production status
// and the Phase 2.5 lifecycle gate, so an intake order never reads "Order
// Received" and a confirmed-but-not-yet-in-production order never reads "Draft".

const LABELS: Record<string, string> = {
  intake_preparation: 'Intake Preparation',
  order_received: 'Order Received',
  draft: 'Draft',
  in_production: 'In Production',
  ready: 'Ready',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
  pending: 'Pending',
}

export function orderDisplayStatus(order: {
  status?: string | null
  lifecycle_status?: string | null
}): { value: string; label: string } {
  const lc = order.lifecycle_status ?? null
  let value = order.status ?? 'draft'

  if (lc === 'cancelled' || value === 'cancelled') {
    value = 'cancelled'
  } else if (lc === 'intake_preparation') {
    value = 'intake_preparation'
  } else if (lc === 'order_received' && value === 'draft') {
    // Confirmed, but items haven't entered production yet.
    value = 'order_received'
  }

  return { value, label: LABELS[value] ?? value }
}
