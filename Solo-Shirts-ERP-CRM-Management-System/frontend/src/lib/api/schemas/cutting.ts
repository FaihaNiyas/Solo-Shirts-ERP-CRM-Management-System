import { z } from 'zod'

export const AllocationSchema = z.object({
  id: z.number(),
  order_item_id: z.number(),
  fabric_roll_id: z.number(),
  roll_number: z.string().optional(),
  allocated_meters: z.number(),
  consumed_meters: z.number().nullable().optional(),
  status: z.enum(['allocated', 'consumed', 'released']),
  created_at: z.string(),
})

export const CuttingBundleSchema = z.object({
  id: z.number(),
  bundle_number: z.string(),
  order_item_id: z.number(),
  order_number: z.string().optional(),
  garment_type: z.string(),
  quantity: z.number(),
  fabric_roll_id: z.number().nullable().optional(),
  status: z.enum(['pending', 'allocated', 'cutting', 'complete']),
  created_at: z.string(),
  updated_at: z.string(),
})

export type Allocation = z.infer<typeof AllocationSchema>
export type CuttingBundle = z.infer<typeof CuttingBundleSchema>
