'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { invalidateOrderCaches } from '@/lib/query/invalidateOrders'
import { apiGet, apiMutate, parseApiData } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'
import { DeliverySchema, type Delivery } from '@/lib/api/schemas/delivery'
import type { PaginatedData } from '@/lib/api/types'

interface DeliveryFilters {
  status?: string | string[]
  from?: string
  to?: string
  page?: number
  per_page?: number
  [key: string]: unknown
}

export function useDeliveries(filters: DeliveryFilters = {}) {
  return useQuery({
    queryKey: queryKeys.deliveries(filters),
    queryFn: () => apiGet<PaginatedData<Delivery>>(ENDPOINTS.deliveries, filters),
    select: (res) => res.data,
  })
}

export function useDelivery(id: number) {
  return useQuery({
    queryKey: queryKeys.delivery(id),
    queryFn: async () => {
      const env = await apiGet<Delivery>(ENDPOINTS.delivery(id))
      parseApiData(env, DeliverySchema) // FE-008/FE-025
      return env
    },
    select: (res) => res.data,
    enabled: id > 0,
  })
}

export function useCreateDelivery() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { order_id: number; notes?: string }) =>
      apiMutate<Delivery>('post', ENDPOINTS.deliveries, data)
        .then((env) => { parseApiData(env, DeliverySchema); return env }), // FE-008/FE-025
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.deliveries() })
      invalidateOrderCaches(qc)
    },
  })
}

export function useDispatchDelivery(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { notes?: string }) =>
      apiMutate('post', ENDPOINTS.dispatchDelivery(id), data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.delivery(id) })
      qc.invalidateQueries({ queryKey: queryKeys.deliveries() })
      invalidateOrderCaches(qc)
    },
  })
}

export function useConfirmDelivery(id: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey() // FE-007: stable key (no double-confirm)
  return useMutation({
    mutationFn: (data: { otp: string }) =>
      apiMutate('post', ENDPOINTS.confirmDelivery(id), data, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.delivery(id) })
      qc.invalidateQueries({ queryKey: queryKeys.deliveries() })
      // A confirmed delivery marks items delivered + frees the rack slot, so the
      // ready-rack and lookup views must drop it without a manual refresh.
      qc.invalidateQueries({ queryKey: queryKeys.rackSearches() })
      qc.invalidateQueries({ queryKey: queryKeys.orderLookups() })
      invalidateOrderCaches(qc)
    },
  })
}

export function useAttemptDelivery(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { reason: string }) =>
      apiMutate('post', ENDPOINTS.attemptDelivery(id), data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.delivery(id) })
      qc.invalidateQueries({ queryKey: queryKeys.deliveries() })
      invalidateOrderCaches(qc)
    },
  })
}

export function useCancelDelivery(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { reason: string }) =>
      apiMutate('post', ENDPOINTS.cancelDelivery(id), data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.delivery(id) })
      qc.invalidateQueries({ queryKey: queryKeys.deliveries() })
      invalidateOrderCaches(qc)
    },
  })
}
