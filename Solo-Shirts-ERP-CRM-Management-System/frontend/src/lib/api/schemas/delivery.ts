import { z } from 'zod'

// FE-025: aligned to DeliveryResource. Real backend fields modelled; FE-only
// fields kept optional; status relaxed to a string.
export const DeliverySchema = z.object({
  id: z.number(),
  order_id: z.number(),
  mode: z.string().optional(),
  status: z.string(),
  courier_partner: z.string().nullable().optional(),
  tracking_no: z.string().nullable().optional(),
  delivery_charges_paise: z.number().nullable().optional(),
  scheduled_at: z.string().nullable().optional(),
  dispatched_at: z.string().nullable().optional(),
  completed_at: z.string().nullable().optional(),
  // Order balance gate (DeliveryResource) — dispatch/confirm blocked while > 0.
  outstanding_paise: z.number().optional(),
  balance_amount: z.number().optional(),
  balance_pending: z.boolean().optional(),
  // Parent order production rollup (present when order.items eager-loaded).
  order_progress: z.object({
    aggregate_status: z.string(),
    aggregate_status_label: z.string(),
    summary_label: z.string(),
  }).nullable().optional(),
  attempts: z.array(z.unknown()).optional(),
  created_at: z.string().optional(),
  // FE-only / aliases (optional):
  delivery_number: z.string().optional(),
  order_number: z.string().optional(),
  customer_id: z.number().optional(),
  customer_name: z.string().nullable().optional(),
  customer_phone: z.string().nullable().optional(),
  branch_id: z.number().optional(),
  delivery_address: z.string().nullable().optional(),
  assigned_staff_id: z.number().nullable().optional(),
  assigned_staff_name: z.string().nullable().optional(),
  delivered_at: z.string().nullable().optional(),
  otp: z.string().nullable().optional(),
  attempt_count: z.number().optional(),
  notes: z.string().nullable().optional(),
  updated_at: z.string().optional(),
})

export type Delivery = z.infer<typeof DeliverySchema>

