import { z } from 'zod'

export const ProductionStateEnum = z.enum([
  'Draft',
  'FabricAllocated',
  'Cutting',
  'Tailoring',
  'KajaButton',
  'Finishing',
  'QC',
  'Packing',
  'ReadyForDelivery',
  'Delivered',
  'Rework',
  'Cancelled',
])

// FE-025: this is the FE board's *transformed* shape (garment_type/production_state
// are derived in useProduction.selectBoard from the raw backend product_type/state).
// Raw ProductionItemResource fields are added below as optional so the type is a
// superset of both shapes. Validation is deferred for Production until the transform
// is centralized (the raw response uses snake_case state/product_type).
export const ProductionItemSchema = z.object({
  id: z.number(),
  order_id: z.number(),
  order_number: z.string().optional(),
  customer_name: z.string().optional(),
  // Position of this sub-order within its parent order ("2 of 5"). Null outside
  // the board (item-detail doesn't eager-load siblings).
  sibling_index: z.number().nullable().optional(),
  sibling_count: z.number().nullable().optional(),
  garment_type: z.string(),
  quantity: z.number(),
  production_state: ProductionStateEnum,
  allowed_transitions: z.array(z.string()),
  assigned_tailor_id: z.number().nullable().optional(),
  assigned_tailor_name: z.string().nullable().optional(),
  rework_count: z.number().optional(),
  expected_delivery_date: z.string().nullable().optional(),
  is_overdue: z.boolean().optional(),
  overdue_days: z.number().optional(),
  notes: z.string().nullable().optional(),
  // Kanban card context (Phase A).
  priority: z.enum(['normal', 'high', 'urgent']).optional(),
  is_on_hold: z.boolean().optional(),
  on_hold_reason: z.string().nullable().optional(),
  assigned_supervisor: z.string().nullable().optional(),
  assigned_supervisors: z.array(z.string()).optional(),
  issue_count: z.number().optional(),
  note_preview: z.string().nullable().optional(),
  last_transition_at: z.string().nullable().optional(),
  delivery_box_code: z.string().nullable().optional(),
  created_at: z.string().optional(),
  updated_at: z.string().optional(),
  // Raw ProductionItemResource fields (optional — present pre-transform):
  item_code: z.string().optional(),
  branch_id: z.number().optional(),
  product_type: z.string().optional(),
  measurement_version_id: z.number().optional(),
  state: z.string().optional(),
  cancelled_at: z.string().nullable().optional(),
  cancel_reason: z.string().nullable().optional(),
})

export const TransitionHistorySchema = z.object({
  id: z.number(),
  from_state: z.string(),
  to_state: z.string(),
  transitioned_by: z.number(),
  transitioned_by_name: z.string().optional(),
  notes: z.string().nullable().optional(),
  created_at: z.string(),
})

export const BoardSchema = z.object({
  columns: z.record(z.array(ProductionItemSchema)),
})

// FE-025: the RAW backend ProductionItemResource (snake_case state/product_type),
// used to validate the production-item detail before the transform is applied.
export const RawProductionItemSchema = z.object({
  id: z.number(),
  order_id: z.number(),
  item_code: z.string().optional(),
  branch_id: z.number().optional(),
  product_type: z.string(),
  quantity: z.number().optional(),
  measurement_version_id: z.number().optional(),
  state: z.string(),
  allowed_transitions: z.array(z.string()).optional(),
  cancelled_at: z.string().nullable().optional(),
  cancel_reason: z.string().nullable().optional(),
}).passthrough()

// Kanban Phase B — production issues (text-only).
export const ProductionIssueSchema = z.object({
  id: z.number(),
  order_item_id: z.number(),
  stage: z.string(),
  issue_type: z.string(),
  description: z.string(),
  status: z.enum(['open', 'resolved']),
  reported_by: z.number().nullable().optional(),
  reporter_name: z.string().nullable().optional(),
  resolved_by: z.number().nullable().optional(),
  resolver_name: z.string().nullable().optional(),
  resolved_at: z.string().nullable().optional(),
  resolution_notes: z.string().nullable().optional(),
  created_at: z.string().nullable().optional(),
})

export const ISSUE_TYPES = [
  'material_defect',
  'machine_problem',
  'measurement_issue',
  'quality_concern',
  'shortage',
  'other',
] as const

export const ISSUE_TYPE_LABELS: Record<string, string> = {
  material_defect: 'Material defect',
  machine_problem: 'Machine problem',
  measurement_issue: 'Measurement issue',
  quality_concern: 'Quality concern',
  shortage: 'Shortage',
  other: 'Other',
}

export type ProductionItem = z.infer<typeof ProductionItemSchema>
export type TransitionHistory = z.infer<typeof TransitionHistorySchema>
export type Board = z.infer<typeof BoardSchema>
export type ProductionIssue = z.infer<typeof ProductionIssueSchema>
