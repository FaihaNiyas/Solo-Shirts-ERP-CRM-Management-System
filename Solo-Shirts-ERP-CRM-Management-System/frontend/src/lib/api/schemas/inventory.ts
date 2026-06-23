import { z } from 'zod'

// FE-025: aligned to the backend inventory resources. Real backend fields are
// modelled (metre values can arrive as number or formatted string); FE-only
// fields kept optional; enums relaxed to strings.

const metres = z.union([z.number(), z.string()])

// FabricRollResource
export const FabricRollSchema = z.object({
  id: z.number(),
  roll_code: z.string().optional(),
  fabric_type_id: z.number(),
  colour: z.string().nullable().optional(),
  supplier_id: z.number().nullable().optional(),
  received_length_metres: metres.optional(),
  remaining_metres: metres.optional(),
  available_metres: metres.optional(),
  reserved_metres: metres.optional(),
  consumed_metres: metres.optional(),
  damaged_metres: metres.optional(),
  low_stock_threshold_metres: metres.nullable().optional(),
  low_stock: z.boolean().optional(),
  unit_price_paise: z.number().nullable().optional(),
  received_date: z.string().nullable().optional(),
  rack_location: z.string().nullable().optional(),
  status: z.string(),
  // FE-only / aliases (optional):
  roll_number: z.string().optional(),
  fabric_type_name: z.string().nullable().optional(),
  supplier_name: z.string().nullable().optional(),
  width_cm: z.number().nullable().optional(),
  total_meters: z.number().optional(),
  remaining_meters: z.number().optional(),
  reserved_meters: z.number().optional(),
  available_meters: z.number().optional(),
  branch_id: z.number().optional(),
  created_at: z.string().optional(),
  updated_at: z.string().optional(),
})

// MovementResource
export const InventoryMovementSchema = z.object({
  id: z.number(),
  fabric_roll_id: z.number().optional(),
  type: z.string(),
  metres: metres.optional(),
  direction: z.string().nullable().optional(),
  reason: z.string().nullable().optional(),
  reference_type: z.string().nullable().optional(),
  reference_id: z.number().nullable().optional(),
  occurred_at: z.string().nullable().optional(),
  // FE-only / aliases (optional):
  roll_id: z.number().optional(),
  meters: z.number().optional(),
  notes: z.string().nullable().optional(),
  created_by: z.number().optional(),
  created_at: z.string().optional(),
})

// FabricTypeResource
export const FabricTypeSchema = z.object({
  id: z.number(),
  name: z.string(),
  code: z.string().optional(),
  low_stock_threshold_metres: metres.optional(),
  is_active: z.boolean().optional(),
  // FE-only:
  description: z.string().nullable().optional(),
  created_at: z.string().optional(),
})

// SupplierResource
export const SupplierSchema = z.object({
  id: z.number(),
  name: z.string(),
  code: z.string().optional(),
  gstin: z.string().nullable().optional(),
  phone: z.string().nullable().optional(),
  email: z.string().nullable().optional(),
  address: z.string().nullable().optional(),
  payment_terms: z.string().nullable().optional(),
  is_active: z.boolean().optional(),
  // FE-only:
  created_at: z.string().optional(),
})

export const PurchaseOrderItemSchema = z.object({
  id: z.number(),
  fabric_type_id: z.number(),
  colour: z.string().nullable().optional(),
  quantity_metres: metres,
  unit_price_paise: z.number(),
  received_metres: metres.optional(),
})

export const PurchaseOrderSchema = z.object({
  id: z.number(),
  po_code: z.string().optional(),
  supplier_id: z.number().optional(),
  supplier_name: z.string().nullable().optional(),
  status: z.string(),
  total_paise: z.number().nullable().optional(),
  notes: z.string().nullable().optional(),
  placed_at: z.string().nullable().optional(),
  items: z.array(PurchaseOrderItemSchema).optional(),
  // FE-only aliases (optional, legacy):
  po_number: z.string().optional(),
  total_amount: z.number().nullable().optional(),
  expected_delivery_date: z.string().nullable().optional(),
  created_by: z.number().optional(),
  created_at: z.string().optional(),
  updated_at: z.string().optional(),
})

export type FabricRoll = z.infer<typeof FabricRollSchema>
export type InventoryMovement = z.infer<typeof InventoryMovementSchema>
export type FabricType = z.infer<typeof FabricTypeSchema>
export type Supplier = z.infer<typeof SupplierSchema>
export type PurchaseOrder = z.infer<typeof PurchaseOrderSchema>
export type PurchaseOrderItem = z.infer<typeof PurchaseOrderItemSchema>

