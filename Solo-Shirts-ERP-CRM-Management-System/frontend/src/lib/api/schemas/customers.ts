import { z } from 'zod'

// FE-025: aligned to CustomerResource (id, branch_id, customer_code, name,
// phone_masked, address, preferred_fabric_id, special_notes, last_measurement_at,
// family_members, created_at). The raw phone is encrypted — only phone_masked is
// returned. FE-only fields kept optional.
export const CustomerSchema = z.object({
  id: z.number(),
  name: z.string(),
  branch_id: z.number().optional(),
  customer_code: z.string().optional(),
  phone_masked: z.string().nullable().optional(),
  address: z.string().nullable().optional(),
  preferred_fabric_id: z.number().nullable().optional(),
  special_notes: z.string().nullable().optional(),
  last_measurement_at: z.string().nullable().optional(),
  family_members: z.array(z.unknown()).optional(),
  created_at: z.string().optional(),
  // FE-only / aliases (optional):
  phone: z.string().nullable().optional(),
  phone_last4: z.string().nullable().optional(),
  email: z.string().nullable().optional(),
  notes: z.string().nullable().optional(),
  last_order_date: z.string().nullable().optional(),
  has_pending_approval: z.boolean().optional(),
  deleted_at: z.string().nullable().optional(),
  updated_at: z.string().optional(),
})

// FamilyMemberResource: id, customer_id, name, relation, dob, gender, notes.
export const FamilyMemberSchema = z.object({
  id: z.number(),
  customer_id: z.number(),
  name: z.string(),
  relation: z.string().nullable().optional(),
  dob: z.string().nullable().optional(),
  gender: z.string().nullable().optional(),
  notes: z.string().nullable().optional(),
  // FE-only:
  created_at: z.string().optional(),
  updated_at: z.string().optional(),
})

export type Customer = z.infer<typeof CustomerSchema>
export type FamilyMember = z.infer<typeof FamilyMemberSchema>

