'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { invalidateOrderCaches } from '@/lib/query/invalidateOrders'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useStableIdempotencyKey } from '@/lib/api/idempotency'

// FE-015: standard hooks for the tailoring-assignment endpoints.

export function useTailoringAssignments(filters: Record<string, unknown> = {}) {
  return useQuery({
    queryKey: queryKeys.tailoringAssignments(filters),
    queryFn: () => apiGet(ENDPOINTS.tailoringAssignments, filters),
    select: (res) => res.data,
  })
}

export function useCreateTailoringAssignment() {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (data: { bundle_id: number; tailor_id: number }) =>
      apiMutate('post', ENDPOINTS.tailoringAssignments, data, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.tailoringAssignments() })
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      invalidateOrderCaches(qc)
    },
  })
}

function useAssignmentAction(action: (id: number) => string, assignmentId: number) {
  const qc = useQueryClient()
  const idem = useStableIdempotencyKey()
  return useMutation({
    mutationFn: (data: Record<string, unknown> = {}) =>
      apiMutate('post', action(assignmentId), data, idem.current),
    onSuccess: () => {
      idem.reset()
      qc.invalidateQueries({ queryKey: queryKeys.tailoringAssignments() })
      qc.invalidateQueries({ queryKey: queryKeys.productionBoard() })
      invalidateOrderCaches(qc)
    },
  })
}

export const useStartTailoring = (id: number) => useAssignmentAction(ENDPOINTS.startTailoring, id)
export const useCompleteTailoring = (id: number) => useAssignmentAction(ENDPOINTS.completeTailoring, id)
export const useReassignTailoring = (id: number) => useAssignmentAction(ENDPOINTS.reassignTailoring, id)
