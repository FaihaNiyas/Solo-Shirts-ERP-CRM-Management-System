'use client'

import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'

export interface ProductionQueueRow {
  item_id: number
  item_code: string
  order_id: number
  order_code: string | null
  product_type: string
  customer_name: string | null
  delivery_date: string | null
  priority: string
  is_rush: boolean
  current_stage: string
  production_box_code: string | null
  placed_in_box: boolean
  fabric: string | null
  style: string | null
  fit: string | null
  measurement_version_id: number | null
  measurement_version: number | null
  job_card_url: string | null
  last_transition_at: string | null
  blockers: string[]
  fabric_status?: 'none' | 'reserved' | 'consumed' | (string & {})
}

/** A fabric reservation/consumption against a sub-order (Phase 7B). */
export interface FabricAllocationView {
  id: number
  order_item_id: number
  fabric_roll_id: number
  reserved_metres: string
  consumed_metres: string | null
  status: 'reserved' | 'consumed' | 'released'
  reserved_at: string | null
  consumed_at: string | null
  released_at: string | null
  roll: { id: number; roll_code: string; remaining_metres: string; available_metres: string } | null
}

/** A single cloth-damage report row (Phase 7B). */
export interface DamageReportRow {
  id: number
  fabric_roll_id: number
  stage: string
  damage_type: string
  quantity_lost_metres: string
  status: 'pending' | 'approved' | 'rejected'
  reported_at: string | null
}

/** Cloth damage / waste totals for an item (Phase 7B). */
export interface ClothDamageSummary {
  count: number
  pending_count: number
  total_metres: string
  approved_metres: string
  recent: DamageReportRow[]
}

/** A single QC inspection attempt (Phase 7C). */
export interface QcInspectionView {
  id: number
  attempt_number: number
  disposition: string
  result: 'passed' | 'failed'
  failure_reason: string | null
  failure_stage: string | null
  rework_target_stage: string | null
  inspector_id: number | null
  inspector_name?: string
  notes: string | null
  inspected_at: string | null
}

/** QC status + rework context + history for an item (Phase 7C). */
export interface QcSummary {
  in_qc: boolean
  in_rework: boolean
  can_inspect: boolean
  attempts: number
  latest: QcInspectionView | null
  rework: {
    target_stage: string | null
    failure_reason: string | null
    failure_stage: string | null
    notes: string | null
  } | null
  history: QcInspectionView[]
}

/** Final packing checklist for a sub-order (Phase 7D). */
export interface PackingChecklistView {
  order_item_id: number
  checked_measurement_card: boolean
  checked_buttons: boolean
  checked_ironing: boolean
  checked_folded: boolean
  checked_packing_cover: boolean
  checked_label: boolean
  is_complete: boolean
  packed_by: number | null
  packed_by_name?: string
  packed_at: string | null
  notes: string | null
}

/** Packing status + ready-rack slot for an item (Phase 7D). */
export interface PackingSummary {
  state: string
  is_packing: boolean
  is_ready: boolean
  is_delivered: boolean
  can_pack: boolean
  checklist: PackingChecklistView | null
  checklist_complete: boolean
  rack_slot: { slot_code: string; label: string | null } | null
  required_checks: string[]
}

export interface ProductionWorkbench extends ProductionQueueRow {
  id: number
  state: string
  allowed_transitions: string[]
  allowed_next_stages: string[]
  measurement_profile: string | null
  measurement: Record<string, number | string>
  notes: string | null
  // Phase 7B
  fabric_status: 'none' | 'reserved' | 'consumed' | (string & {})
  fabric_allocation: FabricAllocationView | null
  cloth_damage: ClothDamageSummary
  // Phase 7C
  qc: QcSummary
  // Phase 7D
  packing: PackingSummary
}

export interface ProductionQueueFilters {
  stage?: string
  product_type?: string
  item_code?: string
  box_code?: string
  order_code?: string
  due?: string
  rush?: boolean
}

export function useProductionQueue(filters: ProductionQueueFilters = {}) {
  return useQuery({
    queryKey: ['production-queue', filters],
    queryFn: () => apiGet<ProductionQueueRow[]>(ENDPOINTS.productionItems, filters as Record<string, unknown>),
    select: (res) => res.data,
    staleTime: 10_000,
  })
}

export function useProductionCodeSearch(q: string, enabled: boolean) {
  return useQuery({
    queryKey: ['production-code-search', q],
    queryFn: () => apiGet<ProductionQueueRow[]>(ENDPOINTS.productionCodeSearch, { q }),
    select: (res) => res.data,
    enabled: enabled && q.trim().length > 0,
  })
}

export function useProductionWorkbench(id: number) {
  return useQuery({
    queryKey: ['production-workbench', id],
    queryFn: () => apiGet<ProductionWorkbench>(ENDPOINTS.productionItem(id)),
    select: (res) => res.data,
    enabled: id > 0,
  })
}

export interface TransitionLog {
  id: number
  from_state: string | null
  to_state: string
  actor_id: number | null
  notes: string | null
  occurred_at: string | null
}

export function useTransitionHistory(id: number) {
  return useQuery({
    queryKey: ['production-history', id],
    queryFn: () => apiGet<TransitionLog[]>(ENDPOINTS.productionHistory(id)),
    select: (res) => res.data,
    enabled: id > 0,
  })
}
