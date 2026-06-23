'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { invalidateOrderCaches } from '@/lib/query/invalidateOrders'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'

// FE-015: standard hooks for the rack slot endpoints (assign is DB-unique-guarded;
// stable keys still prevent a double-submit minting a second request).

export function useRackSlots() {
  return useQuery({
    queryKey: queryKeys.rackSlots(),
    queryFn: () => apiGet(ENDPOINTS.rackSlots),
    select: (res) => res.data,
  })
}

export function useCurrentSlot(itemId: number) {
  return useQuery({
    queryKey: ['rack', 'items', itemId, 'current-slot'],
    queryFn: () => apiGet(ENDPOINTS.currentSlot(itemId)),
    select: (res) => res.data,
    enabled: itemId > 0,
  })
}

export function useAssignRack(itemId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (data: { slot_code?: string }) =>
      apiMutate('post', ENDPOINTS.assignRack(itemId), data, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.rackSlots() })
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      invalidateOrderCaches(qc)
    },
  })
}

export function useReleaseRack(itemId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (data: { reason?: string }) =>
      apiMutate('post', ENDPOINTS.releaseRack(itemId), data, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.rackSlots() })
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      invalidateOrderCaches(qc)
    },
  })
}
