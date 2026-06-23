'use client'

import { useMutation, useQueryClient } from '@tanstack/react-query'
import { apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { invalidateOrderCaches } from '@/lib/query/invalidateOrders'

// Phase 7C — QC pass/fail + rework closure from the production workbench. These
// wrap the production-namespaced QC endpoints (which reuse the Phase 10
// QcInspectionService) and refresh the workbench, queue and history on success.
// This is internal production rework — it never creates a customer alteration.

function refresh(qc: ReturnType<typeof useQueryClient>, itemId: number) {
  qc.invalidateQueries({ queryKey: ['production-workbench', itemId] })
  qc.invalidateQueries({ queryKey: ['production-history', itemId] })
  qc.invalidateQueries({ queryKey: ['production-queue'] })
  // QC pass/fail changes the item state → refresh the parent order's status.
  invalidateOrderCaches(qc)
}

/** Pass QC → item moves to packing. */
export function useQcPass(itemId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { notes?: string }) => apiMutate('post', ENDPOINTS.qcPass(itemId), data),
    onSuccess: () => refresh(qc, itemId),
  })
}

export interface QcFailInput {
  failure_reason: string
  rework_target_stage: string
  failure_stage?: string
  notes?: string
}

/** Fail QC → item parks in internal rework, routed to the target stage. */
export function useQcFail(itemId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: QcFailInput) => apiMutate('post', ENDPOINTS.qcFail(itemId), data),
    onSuccess: () => refresh(qc, itemId),
  })
}
