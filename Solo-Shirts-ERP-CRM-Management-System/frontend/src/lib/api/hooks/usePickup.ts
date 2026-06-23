'use client'

import { useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'

export interface ItemPaymentSummary {
  order_id: number
  item_id: number
  item_code: string
  invoice_id: number | null
  invoice_line_id: number | null
  item_total_paise: number
  allocated_paid_paise: number
  allocated_advance_paise: number
  item_balance_paise: number
  order_balance_paise: number
  production_state: string
  is_ready: boolean
  is_delivered: boolean
  can_collect_item_balance: boolean
  can_handover_item: boolean
  blockers: string[]
}

export interface PickupBatchItem {
  order_item_id: number
  item_code: string
  production_state: string
  item_total_paise: number
  paid_before_paise: number
  amount_due_paise: number
  paid_in_batch_paise: number
  delivered_at: string | null
}

export interface PickupBatchSummary {
  id: number
  batch_no: string
  order_id: number
  pickup_type: string
  payment_mode: string
  status: string
  total_paise: number
  paid_paise: number
  balance_paise: number
  total_amount: number
  paid_amount: number
  balance_amount: number
  order_balance_paise: number
  order_balance_amount: number
  receipt_no: string | null
  can_pay: boolean
  can_handover: boolean
  blockers: string[]
  items: PickupBatchItem[]
}

/**
 * After any pickup money/handover action, refresh everything that derives from
 * the order's items or balance so no manual refresh is needed.
 */
export function invalidatePickup(qc: QueryClient, orderId: number) {
  qc.invalidateQueries({ queryKey: queryKeys.order(orderId) })
  qc.invalidateQueries({ queryKey: queryKeys.orders() })
  qc.invalidateQueries({ queryKey: queryKeys.pickupBatches(orderId) })
  qc.invalidateQueries({ queryKey: ['orders', orderId, 'items'] }) // item payment summaries
  qc.invalidateQueries({ queryKey: ['order-payments', orderId] })
  qc.invalidateQueries({ queryKey: ['handover-eligibility', orderId] })
  qc.invalidateQueries({ queryKey: queryKeys.orderLookups() })
  qc.invalidateQueries({ queryKey: queryKeys.rackSearches() })
  qc.invalidateQueries({ queryKey: queryKeys.frontDeskDashboard() })
  qc.invalidateQueries({ queryKey: ['deliveries'] })
  qc.invalidateQueries({ queryKey: queryKeys.financeOutstanding() })
}

export function useItemPaymentSummary(orderId: number, itemId: number, enabled = true) {
  return useQuery({
    queryKey: queryKeys.itemPaymentSummary(orderId, itemId),
    queryFn: () => apiGet<ItemPaymentSummary>(ENDPOINTS.itemPaymentSummary(orderId, itemId)),
    select: (res) => res.data,
    enabled: enabled && orderId > 0 && itemId > 0,
  })
}

export function useCreatePickupBatch(orderId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { item_ids: number[]; pickup_type?: string }) =>
      apiMutate<PickupBatchSummary>('post', ENDPOINTS.pickupBatches(orderId), data, crypto.randomUUID()).then((r) => r.data),
    onSuccess: () => invalidatePickup(qc, orderId),
  })
}

export function usePickupBatches(orderId: number) {
  return useQuery({
    queryKey: queryKeys.pickupBatches(orderId),
    queryFn: () => apiGet<PickupBatchSummary[]>(ENDPOINTS.pickupBatches(orderId)),
    select: (res) => res.data,
    enabled: orderId > 0,
  })
}

export function usePickupBatch(orderId: number, batchId: number, enabled = true) {
  return useQuery({
    queryKey: queryKeys.pickupBatch(orderId, batchId),
    queryFn: () => apiGet<PickupBatchSummary>(ENDPOINTS.pickupBatch(orderId, batchId)),
    select: (res) => res.data,
    enabled: enabled && orderId > 0 && batchId > 0,
  })
}

export function useCollectPickupPayment(orderId: number, batchId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { amount: number; method: string; reference?: string }) =>
      apiMutate<PickupBatchSummary>('post', ENDPOINTS.pickupBatchPayment(orderId, batchId), data, crypto.randomUUID()).then((r) => r.data),
    onSuccess: () => {
      invalidatePickup(qc, orderId)
      qc.invalidateQueries({ queryKey: queryKeys.pickupBatch(orderId, batchId) })
    },
  })
}

export function useHandoverPickupBatch(orderId: number, batchId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () =>
      apiMutate<Record<string, unknown>>('post', ENDPOINTS.pickupBatchHandover(orderId, batchId), {}).then((r) => r.data),
    onSuccess: () => {
      invalidatePickup(qc, orderId)
      qc.invalidateQueries({ queryKey: queryKeys.pickupBatch(orderId, batchId) })
    },
  })
}
