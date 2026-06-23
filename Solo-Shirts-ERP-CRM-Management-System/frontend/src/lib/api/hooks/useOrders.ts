'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { apiGet, apiMutate, parseApiData } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'
import { OrderSchema, OrderItemSchema, type Order, type OrderItem } from '@/lib/api/schemas/orders'
import type { PaginatedData } from '@/lib/api/types'

interface OrderFilters {
  status?: string | string[]
  customer?: string
  from?: string
  to?: string
  page?: number
  per_page?: number
  [key: string]: unknown
}

export function useOrders(filters: OrderFilters = {}) {
  return useQuery({
    queryKey: queryKeys.orders(filters),
    queryFn: () => apiGet<PaginatedData<Order>>(ENDPOINTS.orders, filters as Record<string, unknown>),
    select: (res) => res.data,
  })
}

export function useOrder(id: number) {
  return useQuery({
    queryKey: queryKeys.order(id),
    queryFn: async () => {
      const env = await apiGet<Order>(ENDPOINTS.order(id))
      parseApiData(env, OrderSchema) // FE-008/FE-025: validate against the backend shape
      return env
    },
    select: (res) => res.data,
    enabled: id > 0,
  })
}

export function useCreateOrder() {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey() // FE-007: stable key across double-submit
  return useMutation({
    mutationFn: async (data: Partial<Order>) => {
      const env = await apiMutate<Order>('post', ENDPOINTS.orders, data, idem.current)
      parseApiData(env, OrderSchema) // FE-008/FE-025
      return env
    },
    onSuccess: (_data, vars) => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.orders() })
      if (vars.customer_id) qc.invalidateQueries({ queryKey: queryKeys.customer(vars.customer_id) }) // FE-014
    },
  })
}

export function useAddOrderItem(orderId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey() // FE-007: stable key across double-submit
  return useMutation({
    mutationFn: async (data: Partial<OrderItem>) => {
      const env = await apiMutate<OrderItem>('post', ENDPOINTS.orderItems(orderId), data, idem.current)
      parseApiData(env, OrderItemSchema) // FE-008/FE-025
      return env
    },
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.order(orderId) })
      qc.invalidateQueries({ queryKey: queryKeys.orders() }) // FE-014: refresh list totals
    },
  })
}

export function useCancelOrder(orderId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (reason: string) =>
      apiMutate<Order>('post', ENDPOINTS.cancelOrder(orderId), { reason }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.order(orderId) })
      qc.invalidateQueries({ queryKey: queryKeys.orders() })
    },
  })
}

export function useUpdateOrder(orderId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { expected_delivery_date?: string | null; delivery_mode?: string; notes?: string | null; source?: string }) =>
      apiMutate<Order>('put', ENDPOINTS.order(orderId), data).then((r) => r.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: queryKeys.order(orderId) })
      qc.invalidateQueries({ queryKey: queryKeys.orders() })
    },
  })
}
