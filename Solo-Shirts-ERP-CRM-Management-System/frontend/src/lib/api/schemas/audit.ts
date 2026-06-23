import { z } from 'zod'

export const AuditActivitySchema = z.object({
  id: z.number(),
  subject_type: z.string(),
  subject_id: z.number(),
  event: z.string(),
  description: z.string().nullable().optional(),
  properties: z.record(z.unknown()).nullable().optional(),
  actor_id: z.number().nullable().optional(),
  actor_name: z.string().nullable().optional(),
  branch_id: z.number().nullable().optional(),
  created_at: z.string(),
})

export const TransitionHistoryItemSchema = z.object({
  id: z.number(),
  order_item_id: z.number(),
  from_state: z.string(),
  to_state: z.string(),
  notes: z.string().nullable().optional(),
  transitioned_by: z.number(),
  transitioned_by_name: z.string().optional(),
  created_at: z.string(),
})

export type AuditActivity = z.infer<typeof AuditActivitySchema>
export type TransitionHistoryItem = z.infer<typeof TransitionHistoryItemSchema>
