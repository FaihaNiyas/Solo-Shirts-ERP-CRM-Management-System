// Maps wizard state → the backend CreateOrderRequest contract. The backend
// accepts a per-item measurement_version_id plus a JSON design_notes array, so
// fabric/style/fit ride along with zero schema changes.

import type { Customer } from '@/lib/api/schemas/customers'
import type { MainOrderMeta, SubOrderDraft } from './types'

export function buildOrderPayload(
  customer: Customer,
  memberLabel: string,
  meta: MainOrderMeta,
  subOrders: SubOrderDraft[],
) {
  return {
    customer_id: customer.id,
    source: meta.source,
    // Created as an intake order — gated out of production until final Confirm.
    lifecycle_status: 'intake_preparation' as const,
    expected_delivery_date: meta.deliveryDate || null,
    delivery_mode: meta.deliveryMode,
    notes: meta.notes || null,
    items: subOrders.map((s) => ({
      // 1 sub-order = 1 shirt or 1 trouser (backend product_type: shirt | pant).
      product_type: (s.productType === 'trouser' ? 'pant' : 'shirt') as 'shirt' | 'pant',
      quantity: 1,
      measurement_version_id: s.measurementVersionId,
      fabric_preference_text: s.fabricLabel ?? null,
      design_notes: {
        fabric_id: s.fabricId,
        fabric: s.fabricLabel,
        style_id: s.styleId,
        style: s.styleLabel,
        fit_id: s.fitId,
        fit: s.fitLabel,
        member: memberLabel,
        notes: s.notes || null,
      },
    })),
  }
}
