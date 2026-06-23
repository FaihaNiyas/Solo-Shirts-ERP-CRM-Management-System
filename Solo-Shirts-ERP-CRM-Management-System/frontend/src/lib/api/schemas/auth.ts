import { z } from 'zod'

export const BranchSchema = z.object({
  id: z.number(),
  name: z.string(),
  code: z.string(),
  address: z.string().optional().nullable(),
  is_active: z.boolean(),
})

export const UserSchema = z.object({
  id: z.number(),
  name: z.string(),
  email: z.string().email(),
  roles: z.array(z.string()),
  permissions: z.array(z.string()),
  branch_id: z.number().nullable(),
  branch: BranchSchema.nullable(),
  two_factor_enabled: z.boolean(),
})

export const AuthSessionSchema = z.object({
  user: UserSchema,
  token: z.string(),
  expires_at: z.string(),
  requires_2fa: z.boolean().optional(),
})

export type Branch = z.infer<typeof BranchSchema>
export type User = z.infer<typeof UserSchema>
export type AuthSession = z.infer<typeof AuthSessionSchema>
