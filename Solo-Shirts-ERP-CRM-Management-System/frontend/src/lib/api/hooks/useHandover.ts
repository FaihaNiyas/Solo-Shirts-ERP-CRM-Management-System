'use client'

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'
import { invalidateOrderCaches } from '@/lib/query/invalidateOrders'
import { orderPaymentsKey } from './useOrderPayments'
import type { OrderProgress } from './useFrontDeskLookup'

export interface HandoverSubOrder {
  item_code: string
  status: string
  ready: boolean
  rack_slot: string | null
}

export interface HandoverEligibility {
  order_id: number
  order_code: string
  customer_name: string | null
  lifecycle_status: string
  delivery_mode: string | null
  ready: boolean
  progress?: OrderProgress
  ready_count?: number
  not_ready_count?: number
  balance_amount: number
  payment_status: string | null
  can_handover: boolean
  blockers: string[]
  warnings: string[]
  rack_slots: string[]
  sub_orders: HandoverSubOrder[]
}

export interface HandoverResult {
  order_id: number
  order_code: string
  status: string
  delivered_sub_orders: string[]
  released_rack_slots: string[]
}

export function handoverEligibilityKey(orderId: number) {
  return queryKeys.handoverEligibility(orderId)
}

export function useHandoverEligibility(orderId: number) {
  return useQuery({
    queryKey: handoverEligibilityKey(orderId),
    queryFn: () => apiGet<HandoverEligibility>(ENDPOINTS.handoverEligibility(orderId)),
    select: (res) => res.data,
    enabled: orderId > 0,
  })
}

export function useHandover(orderId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { mode: string; notes?: string }) =>
      apiMutate<HandoverResult>('post', ENDPOINTS.handover(orderId), data).then((r) => r.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: handoverEligibilityKey(orderId) })
      qc.invalidateQueries({ queryKey: orderPaymentsKey(orderId) })
      // Handover marks the order's items delivered → refresh the order status.
      invalidateOrderCaches(qc)
    },
  })
}
