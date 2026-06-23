'use client'

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'

export interface DraftRow {
  id: number
  title: string | null
  status: string
  current_step: string | null
  completed_count: number
  total_items: number
  customer_id: number | null
  customer_name: string | null
  order_id: number | null
  created_by: string | null
  last_saved_at: string | null
}

export function useFrontDeskDrafts() {
  return useQuery({
    queryKey: ['front-desk-drafts'],
    queryFn: () => apiGet<DraftRow[]>(ENDPOINTS.frontDeskDrafts),
    select: (res) => res.data,
    staleTime: 15_000,
  })
}

export function useDiscardDraft() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiMutate('delete', ENDPOINTS.frontDeskDraft(id)),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['front-desk-drafts'] }),
  })
}
