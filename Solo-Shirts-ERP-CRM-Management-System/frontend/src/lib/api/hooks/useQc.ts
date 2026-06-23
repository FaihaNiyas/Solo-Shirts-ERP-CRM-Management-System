'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { invalidateOrderCaches } from '@/lib/query/invalidateOrders'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'

// FE-015: standard hooks for the QC endpoints (inspect is state-guarded + transactional
// on the backend; a stable key prevents a UI double-submit).

export function useQcHistory(itemId: number) {
  return useQuery({
    queryKey: queryKeys.qcHistory(itemId),
    queryFn: () => apiGet(ENDPOINTS.qcHistory(itemId)),
    select: (res) => res.data,
    enabled: itemId > 0,
  })
}

export function useDefectCategories() {
  return useQuery({
    queryKey: queryKeys.defectCategories(),
    queryFn: () => apiGet(ENDPOINTS.defectCategories),
    select: (res) => res.data,
    staleTime: 10 * 60 * 1000,
  })
}

export function useInspectItem(itemId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (data: { disposition: string; notes?: string; defects?: string[] }) =>
      apiMutate('post', ENDPOINTS.inspectItem(itemId), data, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      qc.invalidateQueries({ queryKey: queryKeys.qcHistory(itemId) })
      invalidateOrderCaches(qc)
    },
  })
}

export function useReworkOverride(itemId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (data: { notes?: string }) =>
      apiMutate('post', ENDPOINTS.reworkOverride(itemId), data, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      qc.invalidateQueries({ queryKey: queryKeys.qcHistory(itemId) })
      invalidateOrderCaches(qc)
    },
  })
}
