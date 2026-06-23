'use client'

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiMutate, apiPost } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'

// Alteration workflow statuses (Phase 5B). delivered/cancelled are terminal.
export type AlterationStatus =
  | 'intake'
  | 'approved'
  | 'in_alteration'
  | 'ready'
  | 'delivered'
  | 'cancelled'

export const ALTERATION_STATUS_LABELS: Record<string, string> = {
  intake: 'Intake',
  approved: 'Approved',
  in_alteration: 'In alteration',
  ready: 'Ready',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
}

// Action label shown for a transition INTO a given status.
export const ALTERATION_ACTION_LABELS: Record<string, string> = {
  approved: 'Approve',
  in_alteration: 'Start alteration',
  ready: 'Mark ready',
  delivered: 'Mark delivered',
  cancelled: 'Cancel alteration',
}

// Suggested issue types — mirrors AlterationRequest::ISSUE_TYPES on the backend.
// Deliberately free of QC defect codes: this is a customer-facing alteration,
// NOT an internal rework reason.
export const ALTERATION_ISSUE_TYPES = [
  'fitting_issue',
  'stitching_issue',
  'length_adjustment',
  'fabric_issue',
  'button_zip_issue',
  'other',
] as const

export type AlterationIssueType = (typeof ALTERATION_ISSUE_TYPES)[number]

export const ALTERATION_ISSUE_LABELS: Record<AlterationIssueType, string> = {
  fitting_issue: 'Fitting issue',
  stitching_issue: 'Stitching issue',
  length_adjustment: 'Length adjustment',
  fabric_issue: 'Fabric issue',
  button_zip_issue: 'Button / zip issue',
  other: 'Other',
}

export type AlterationPriority = 'normal' | 'urgent'

export interface AlterationListRow {
  id: number
  status: string
  priority: string
  issue_type: string
  customer_name: string | null
  // Full customer phone for allowed roles (Front Desk / Admin / Owner); null
  // otherwise. Always prefer this over phone_masked when present.
  phone: string | null
  phone_masked: string | null
  order_code: string | null
  item_code: string | null
  charge_required: boolean
  estimated_charge: number | null
  created_at: string | null
  photo_url: string | null
  issue_preview?: string
}

export interface AlterationStatusLog {
  id: number
  previous_status: string
  new_status: string
  changed_by: string | null
  notes: string | null
  created_at: string | null
}

export interface AlterationDetail extends AlterationListRow {
  issue_description: string
  created_by: string | null
  product_type: string | null
  fabric: string | null
  style: string | null
  fit: string | null
  allowed_next_statuses: string[]
  can_update_status: boolean
  completed_at: string | null
  cancelled_at: string | null
  status_logs: AlterationStatusLog[]
}

export interface AlterationFilters {
  q?: string
  status?: string
  priority?: string
  order_id?: number
}

export interface CreateAlterationInput {
  original_order_item_id: number
  issue_type: AlterationIssueType
  issue_description: string
  priority?: AlterationPriority
  charge_required?: boolean
  estimated_charge?: number | null
  photo?: File | null
}

export interface CreateAlterationResult {
  alteration_id: number
  status: string
  original_order_code: string | null
  original_item_code: string | null
  customer_name: string | null
}

export function alterationsKey(filters: AlterationFilters = {}) {
  return ['alterations', filters] as const
}

export function useAlterations(filters: AlterationFilters = {}, enabled = true) {
  return useQuery({
    queryKey: alterationsKey(filters),
    queryFn: () => apiGet<AlterationListRow[]>(ENDPOINTS.alterations, filters as Record<string, unknown>),
    select: (res) => res.data,
    enabled,
  })
}

export function useAlteration(id: number) {
  return useQuery({
    queryKey: ['alteration', id],
    queryFn: () => apiGet<AlterationDetail>(ENDPOINTS.alteration(id)),
    select: (res) => res.data,
    enabled: id > 0,
  })
}

export function useCreateAlteration() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: CreateAlterationInput) => {
      // Multipart because an optional intake photo may ride along; axios strips
      // the JSON content-type for FormData and sets the multipart boundary.
      const fd = new FormData()
      fd.append('original_order_item_id', String(input.original_order_item_id))
      fd.append('issue_type', input.issue_type)
      fd.append('issue_description', input.issue_description)
      fd.append('priority', input.priority ?? 'normal')
      fd.append('charge_required', input.charge_required ? '1' : '0')
      if (input.estimated_charge != null && !Number.isNaN(input.estimated_charge)) {
        fd.append('estimated_charge', String(input.estimated_charge))
      }
      if (input.photo) fd.append('photo', input.photo)

      return apiPost<CreateAlterationResult>(ENDPOINTS.alterations, fd).then((r) => r.data)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['alterations'] })
    },
  })
}

export interface UpdateAlterationStatusResult {
  alteration_id: number
  status: string
  previous_status: string
  updated_by: string | null
  updated_at: string | null
}

export function useUpdateAlterationStatus(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: { status: string; notes?: string }) =>
      apiMutate<UpdateAlterationStatusResult>('patch', ENDPOINTS.alterationStatus(id), input).then((r) => r.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['alteration', id] })
      qc.invalidateQueries({ queryKey: ['alterations'] })
    },
  })
}
