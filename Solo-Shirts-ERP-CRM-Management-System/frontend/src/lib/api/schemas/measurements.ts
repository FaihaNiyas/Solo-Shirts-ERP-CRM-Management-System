import { z } from 'zod'

export const ShirtDataSchema = z.object({
  chest:         z.number().nullable().optional(),
  waist:         z.number().nullable().optional(),
  hip:           z.number().nullable().optional(),
  shoulder:      z.number().nullable().optional(),
  sleeve_length: z.number().nullable().optional(),
  shirt_length:  z.number().nullable().optional(),
  collar:        z.number().nullable().optional(),
  cuff:          z.number().nullable().optional(),
  arm_round:     z.number().nullable().optional(),
  neck:          z.number().nullable().optional(),
  front_chest:   z.number().nullable().optional(),
  cross_back:    z.number().nullable().optional(),
  dart:          z.number().nullable().optional(),
  bicep:         z.number().nullable().optional(),
  wrist:         z.number().nullable().optional(),
  note_1:        z.string().nullable().optional(),
  note_2:        z.string().nullable().optional(),
  note_3:        z.string().nullable().optional(),
  note_4:        z.string().nullable().optional(),
  note_5:        z.string().nullable().optional(),
}).passthrough()

export const PantDataSchema = z.object({
  waist:    z.number().nullable().optional(),
  hip:      z.number().nullable().optional(),
  thigh:    z.number().nullable().optional(),
  knee:     z.number().nullable().optional(),
  bottom:   z.number().nullable().optional(),
  length:   z.number().nullable().optional(),
  in_seam:  z.number().nullable().optional(),
  out_seam: z.number().nullable().optional(),
  crotch:   z.number().nullable().optional(),
  fly:      z.number().nullable().optional(),
  note_1:   z.string().nullable().optional(),
  note_2:   z.string().nullable().optional(),
  note_3:   z.string().nullable().optional(),
  note_4:   z.string().nullable().optional(),
  note_5:   z.string().nullable().optional(),
}).passthrough()

// FE-025: aligned to MeasurementVersionResource (id, profile_id, version_number,
// status, shirt_data, pant_data, significant_change, diff_json, effective_from,
// effective_to, created_by, approved_by, approved_at, rejection_reason).
export const MeasurementVersionSchema = z.object({
  id: z.number(),
  profile_id: z.number(),
  version_number: z.number(),
  status: z.string(),
  shirt_data: ShirtDataSchema.nullable().optional(),
  pant_data: PantDataSchema.nullable().optional(),
  significant_change: z.boolean().nullable().optional(),
  diff_json: z.record(z.unknown()).nullable().optional(),
  effective_from: z.string().nullable().optional(),
  effective_to: z.string().nullable().optional(),
  created_by: z.number().nullable().optional(),
  approved_by: z.number().nullable().optional(),
  approved_at: z.string().nullable().optional(),
  rejection_reason: z.string().nullable().optional(),
  // FE-only (optional):
  approval_note: z.string().nullable().optional(),
  created_at: z.string().optional(),
  updated_at: z.string().optional(),
})

// MeasurementProfileResource: id, branch_id, customer_id, family_member_id, name,
// type, is_default, current_version.
export const MeasurementProfileSchema = z.object({
  id: z.number(),
  customer_id: z.number(),
  family_member_id: z.number().nullable().optional(),
  branch_id: z.number().optional(),
  name: z.string().nullable().optional(),
  type: z.string().nullable().optional(),
  is_default: z.boolean().optional(),
  current_version: z.unknown().optional(),
  // FE-only / aliases (optional):
  label: z.string().nullable().optional(),
  latest_approved_version_id: z.number().nullable().optional(),
  latest_version_id: z.number().nullable().optional(),
  created_at: z.string().optional(),
  updated_at: z.string().optional(),
})

// Pending-approval feed — a bespoke shape; kept tolerant.
export const PendingApprovalSchema = z.object({
  id: z.number(),
  version_id: z.number(),
  customer_name: z.string().optional(),
  version_number: z.number().optional(),
  changed_fields: z.array(z.string()).optional(),
  delta_summary: z.string().optional(),
  submitted_by: z.string().optional(),
  submitted_at: z.string().optional(),
}).passthrough()

export type MeasurementVersion = z.infer<typeof MeasurementVersionSchema>
export type MeasurementProfile = z.infer<typeof MeasurementProfileSchema>
export type PendingApproval = z.infer<typeof PendingApprovalSchema>

