'use client'

import { useMutation, useQueryClient } from '@tanstack/react-query'
import { apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { invalidateOrderCaches } from '@/lib/query/invalidateOrders'

// Phase 7D — final packing from the production workbench. Saving the checklist
// and marking packed both refresh the workbench + queue. Marking packed promotes
// the item to ready-for-delivery (auto-assigns a rack slot); it never marks
// delivered and never touches the balance (the balance gate stays at handover).

function refresh(qc: ReturnType<typeof useQueryClient>, itemId: number) {
  qc.invalidateQueries({ queryKey: ['production-workbench', itemId] })
  qc.invalidateQueries({ queryKey: ['production-history', itemId] })
  qc.invalidateQueries({ queryKey: ['production-queue'] })
  // Marking packed promotes the item to ready-for-delivery → refresh the order.
  invalidateOrderCaches(qc)
}

export type PackingChecklistInput = Partial<{
  checked_measurement_card: boolean
  checked_buttons: boolean
  checked_ironing: boolean
  checked_folded: boolean
  checked_packing_cover: boolean
  checked_label: boolean
  notes: string
}>

/** Save/patch the packing checklist (only while the item is in packing). */
export function useSavePackingChecklist(itemId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: PackingChecklistInput) => apiMutate('post', ENDPOINTS.packingChecklist(itemId), data),
    onSuccess: () => refresh(qc, itemId),
  })
}

/** Mark packed → promote to ready-for-delivery + auto-assign a rack slot. */
export function useMarkPacked(itemId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => apiMutate('post', ENDPOINTS.markPacked(itemId), {}),
    onSuccess: () => refresh(qc, itemId),
  })
}
