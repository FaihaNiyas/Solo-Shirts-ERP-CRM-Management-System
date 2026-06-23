'use client'

import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'

export interface OrderProgress {
  aggregate_status: string
  aggregate_status_label: string
  progress: {
    total: number
    draft: number
    in_production: number
    ready: number
    delivered: number
    cancelled: number
    active: number
  }
  summary_label: string
}

export interface LookupItem {
  id: number
  item_code: string
  product_type: string
  status: string
  status_label: string
  fabric: string | null
  style: string | null
  fit: string | null
  box_code: string | null
  placed_in_box: boolean
  pdf_generated: boolean
  ready_rack_slot: string | null
  delivery_box_code: string | null
}

export interface LookupInvoice {
  invoice_number: string
  total_amount: number
  paid_amount: number
  balance_amount: number
  payment_status: string
}

export interface LookupOrder {
  id: number
  order_code: string
  customer_name: string | null
  phone_masked: string | null
  lifecycle_status: string
  status: string
  progress: OrderProgress
  order_date: string | null
  delivery_date: string | null
  is_rush: boolean
  invoice: LookupInvoice | null
  items: LookupItem[]
}

export interface RackResult {
  order_id: number
  order_code: string
  customer_name: string | null
  phone_masked: string | null
  delivery_date: string | null
  balance_amount: number
  payment_status: string | null
  ready: boolean
  current_status: string
  progress: OrderProgress
  rack_slots: { slot_code: string; order_item_id: number }[]
  ready_sub_orders: { item_code: string; product_type: string | null; ready_rack_slot: string | null }[]
  other_items: { item_code: string; product_type: string | null; status: string; status_label: string }[]
}

export function useOrderLookup(q: string) {
  return useQuery({
    queryKey: queryKeys.orderLookup(q),
    queryFn: () => apiGet<{ query: string; results: LookupOrder[] }>(ENDPOINTS.orderLookup, { q }),
    select: (res) => res.data.results,
    enabled: q.trim().length >= 2,
  })
}

export function useRackSearch(q: string) {
  return useQuery({
    queryKey: queryKeys.rackSearch(q),
    queryFn: () => apiGet<{ query: string; results: RackResult[] }>(ENDPOINTS.rackSearch, { q }),
    select: (res) => res.data.results,
    enabled: q.trim().length >= 2,
  })
}

// Re-exported from the single canonical mapper so every screen shares one map.
export { productionStateLabel } from '@/lib/orders/productionState'
