import { z } from 'zod'

// FE-025: aligned to the backend resources (OrderResource / OrderListResource /
// OrderItemResource). Fields the backend genuinely does NOT expose on orders are
// kept as optional placeholders and flagged as backend gaps, so the UI compiles
// and components can render real data where it exists.

export const OrderItemSchema = z.object({
  // OrderItemResource (real backend fields):
  id: z.number(),
  item_code: z.string(),
  product_type: z.string(),
  quantity: z.number(),
  measurement_version_id: z.number(),
  fabric_preference_text: z.string().nullable().optional(),
  // Stored as a structured JSON object at intake (fabric/style/fit/pricing), not a
  // plain string. The UI reads the mapped *_summary fields, so keep this permissive.
  design_notes: z.unknown().nullable().optional(),
  state: z.string(),
  // Display-ready production fields (OrderItemResource — one canonical mapper):
  production_state: z.string().optional(),
  production_state_label: z.string().nullable().optional(),
  fabric_summary: z.string().nullable().optional(),
  style_summary: z.string().nullable().optional(),
  fit_summary: z.string().nullable().optional(),
  is_ready_for_handover: z.boolean().optional(),
  is_delivered: z.boolean().optional(),
  ready_rack_slot: z.string().nullable().optional(),
  cancelled_at: z.string().nullable().optional(),
  cancel_reason: z.string().nullable().optional(),
  // Phase 2 — production box & placement:
  production_box_id: z.number().nullable().optional(),
  box_code: z.string().nullable().optional(),
  placed_in_box: z.boolean().optional(),
  placed_in_box_at: z.string().nullable().optional(),
  // Backend gaps (not on OrderItemResource) — kept optional for the UI:
  assigned_tailor_id: z.number().nullable().optional(),
  assigned_tailor_name: z.string().nullable().optional(),
  allowed_transitions: z.array(z.string()).optional(),
  rework_count: z.number().optional(),
})

export const OrderSchema = z.object({
  // OrderResource + OrderListResource (real backend fields):
  id: z.number(),
  order_code: z.string(),
  branch_id: z.number().optional(), // present in detail, absent in the list resource
  customer_id: z.number(),
  source: z.string().nullable().optional(),
  channel_notes: z.string().nullable().optional(),
  expected_delivery_date: z.string().nullable().optional(),
  delivery_mode: z.string().nullable().optional(),
  delivery_charges_paise: z.number().nullable().optional(),
  notes: z.string().nullable().optional(),
  status: z.string(),
  // Phase 2.5 — lifecycle gate (intake_preparation / order_received / cancelled).
  lifecycle_status: z.string().nullable().optional(),
  items: z.array(OrderItemSchema).optional(),
  item_count: z.number().nullable().optional(),
  // Derived production rollup (OrderResource / OrderListResource).
  progress: z.object({
    aggregate_status: z.string(),
    aggregate_status_label: z.string(),
    progress: z.object({
      total: z.number(),
      draft: z.number(),
      in_production: z.number(),
      ready: z.number(),
      delivered: z.number(),
      cancelled: z.number(),
      active: z.number(),
    }),
    summary_label: z.string(),
  }).nullable().optional(),
  created_at: z.string(),
  // Backend gaps (not on the order resources) — kept optional for the UI:
  customer_name: z.string().nullable().optional(),
  customer_phone: z.string().nullable().optional(),
  customer_phone_masked: z.string().nullable().optional(),
  branch_name: z.string().nullable().optional(),
  total_amount: z.number().nullable().optional(),
  balance_due: z.number().nullable().optional(),
})

export type Order = z.infer<typeof OrderSchema>
export type OrderItem = z.infer<typeof OrderItemSchema>
