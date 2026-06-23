'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { invalidateOrderCaches } from '@/lib/query/invalidateOrders'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'
import type { ApiEnvelope } from '@/lib/api/types'

type CuttingQueueEnvelope = ApiEnvelope<CuttingQueueItem[]>

/**
 * Optimistically flip one queue item's state so the row reacts instantly, with a
 * snapshot for rollback if the server rejects. Returns the rollback context.
 */
async function optimisticState(
  qc: ReturnType<typeof useQueryClient>,
  itemId: number,
  nextState: CuttingQueueItem['state'],
): Promise<{ prev: CuttingQueueEnvelope | undefined }> {
  const key = queryKeys.cuttingQueue()
  await qc.cancelQueries({ queryKey: key })
  const prev = qc.getQueryData<CuttingQueueEnvelope>(key)
  qc.setQueryData<CuttingQueueEnvelope>(key, (old) =>
    old?.data
      ? { ...old, data: old.data.map((it) => (it.id === itemId ? { ...it, state: nextState } : it)) }
      : old,
  )
  return { prev }
}

// FE-002 — the cutting screen drives the real cutting/fabric endpoints (NOT the
// generic production-transition shortcut). Types here mirror the backend
// resources directly (CuttingQueueItemResource / FabricRollResource) so the
// values shown are real; we don't rely on the drifted shared schemas (FE-025).

export interface CuttingQueueItem {
  id: number
  order_id: number
  item_code: string
  product_type: string
  quantity: number
  state: 'draft' | 'fabric_allocated' | 'cutting' | (string & {})
  fabric_preference_text: string | null
  measurement_version_id: number
}

/** Minimal roll shape from the backend fabric-rolls list, for the allocate picker. */
export interface AllocatableRoll {
  id: number
  roll_code: string
  fabric_type_id: number
  colour: string | null
  remaining_metres: number
  available_metres: number
  status: string
}

export interface CompleteCuttingBundleInput {
  pieces: number
  notes?: string
}

// ---- Queries ----

export function useCuttingQueue() {
  return useQuery({
    queryKey: queryKeys.cuttingQueue(),
    queryFn: () => apiGet<CuttingQueueItem[]>(ENDPOINTS.cuttingQueue),
    select: (res) => res.data,
  })
}

/** Available rolls for allocation. Fetched with a backend-accurate local type. */
export function useAllocatableRolls() {
  return useQuery({
    queryKey: queryKeys.fabricRolls({ status: 'available' }),
    queryFn: () =>
      apiGet<{ data: AllocatableRoll[] }>(ENDPOINTS.fabricRolls, { status: 'available', per_page: 100 }),
    select: (res) => res.data.data ?? [],
  })
}

export function useBundle(id: number) {
  return useQuery({
    queryKey: queryKeys.cuttingBundle(id),
    queryFn: () => apiGet(ENDPOINTS.cuttingBundle(id)),
    select: (res) => res.data,
    enabled: id > 0,
  })
}


// ---- Mutations (all use real cutting endpoints + stable Idempotency-Key) ----

/** Reserve fabric for an item (2-phase). Backend requires an Idempotency-Key. */
export function useAllocateFabric(itemId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (data: { roll_id: number; metres: number }) =>
      apiMutate('post', ENDPOINTS.allocateFabric(itemId), data, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.cuttingQueue() })
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      qc.invalidateQueries({ queryKey: queryKeys.fabricRolls() })
      qc.invalidateQueries({ queryKey: queryKeys.inventoryMovements({}) })
      invalidateOrderCaches(qc)
    },
  })
}

export function useReleaseFabric(itemId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (data: { reason?: string }) =>
      apiMutate('post', ENDPOINTS.releaseFabric(itemId), data, idem.current),
    // Releasing reverts the item to "Awaiting fabric" — flip it instantly.
    onMutate: () => optimisticState(qc, itemId, 'draft'),
    onError: (_e, _v, ctx) => {
      if (ctx?.prev) qc.setQueryData(queryKeys.cuttingQueue(), ctx.prev)
    },
    onSuccess: () => idem.reset(),
    onSettled: () => {
      qc.invalidateQueries({ queryKey: queryKeys.cuttingQueue() })
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      qc.invalidateQueries({ queryKey: queryKeys.fabricRolls() })
      invalidateOrderCaches(qc)
    },
  })
}

export function useStartCutting(itemId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: () => apiMutate('post', ENDPOINTS.startCutting(itemId), {}, idem.current),
    // Starting moves the item to "Cutting" — flip it instantly.
    onMutate: () => optimisticState(qc, itemId, 'cutting'),
    onError: (_e, _v, ctx) => {
      if (ctx?.prev) qc.setQueryData(queryKeys.cuttingQueue(), ctx.prev)
    },
    onSuccess: () => idem.reset(),
    onSettled: () => {
      qc.invalidateQueries({ queryKey: queryKeys.cuttingQueue() })
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      invalidateOrderCaches(qc)
    },
  })
}

/** Consume the reserved fabric and create cut bundles; item advances to tailoring. */
export function useCompleteCutting(itemId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (data: { actual_metres: number; bundles: CompleteCuttingBundleInput[] }) =>
      apiMutate('post', ENDPOINTS.completeCutting(itemId), data, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.cuttingQueue() })
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      qc.invalidateQueries({ queryKey: queryKeys.fabricRolls() })
      qc.invalidateQueries({ queryKey: queryKeys.inventoryMovements({}) })
      qc.invalidateQueries({ queryKey: queryKeys.tailoringAssignments() })
      invalidateOrderCaches(qc)
    },
  })
}
