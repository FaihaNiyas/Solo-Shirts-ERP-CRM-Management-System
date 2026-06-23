'use client'

import { useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { invalidateOrderCaches } from '@/lib/query/invalidateOrders'
import { apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'

// Phase 7B — fabric allocation + cloth damage driven from the production
// workbench. These wrap the production-namespaced endpoints (which reuse the
// Phase 8/12 services) and refresh the workbench + inventory views on success.

function refreshWorkbench(qc: ReturnType<typeof useQueryClient>, itemId: number) {
  qc.invalidateQueries({ queryKey: ['production-workbench', itemId] })
  qc.invalidateQueries({ queryKey: ['production-queue'] })
  // Allocating/consuming fabric advances the item state → refresh the order.
  invalidateOrderCaches(qc)
}

/** Reserve fabric against the item (idempotent — backend requires the key). */
export function useAllocateProductionFabric(itemId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (data: { roll_id: number; metres: number }) =>
      apiMutate('post', ENDPOINTS.productionFabric(itemId), data, idem.current),
    onSuccess: () => {
      idem.reset()
      refreshWorkbench(qc, itemId)
      qc.invalidateQueries({ queryKey: queryKeys.fabricRolls() })
      qc.invalidateQueries({ queryKey: queryKeys.inventoryMovements({}) })
    },
  })
}

/** Record actual usage and close the reservation as consumed. */
export function useConsumeProductionFabric(itemId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { actual_metres?: number }) =>
      apiMutate('patch', ENDPOINTS.productionConsumeFabric(itemId), data),
    onSuccess: () => {
      refreshWorkbench(qc, itemId)
      qc.invalidateQueries({ queryKey: queryKeys.fabricRolls() })
      qc.invalidateQueries({ queryKey: queryKeys.inventoryMovements({}) })
    },
  })
}

export interface ReportItemDamageInput {
  stage: string
  damage_type: string
  damage_type_other?: string
  quantity_lost_metres: number
  action_taken?: string
}

/** Report cloth damage / waste for the item (roll derived from its allocation). */
export function useReportItemDamage(itemId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: ReportItemDamageInput) =>
      apiMutate('post', ENDPOINTS.productionClothDamage(itemId), data),
    onSuccess: () => {
      refreshWorkbench(qc, itemId)
      qc.invalidateQueries({ queryKey: [ENDPOINTS.clothDamage] })
    },
  })
}
