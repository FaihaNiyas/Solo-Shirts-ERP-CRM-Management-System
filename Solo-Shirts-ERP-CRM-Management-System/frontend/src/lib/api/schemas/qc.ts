import { z } from 'zod'

export const DefectCategorySchema = z.object({
  id: z.number(),
  name: z.string(),
  code: z.string(),
  severity: z.enum(['minor', 'major', 'critical']).optional(),
})

export const QCInspectionSchema = z.object({
  id: z.number(),
  order_item_id: z.number(),
  result: z.enum(['pass', 'fail', 'rework']),
  defect_codes: z.array(z.string()).optional(),
  notes: z.string().nullable().optional(),
  inspector_id: z.number(),
  inspector_name: z.string().optional(),
  photos: z.array(z.string()).optional(),
  created_at: z.string(),
})

export const QCHistorySchema = z.object({
  inspections: z.array(QCInspectionSchema),
  rework_count: z.number(),
})

export type DefectCategory = z.infer<typeof DefectCategorySchema>
export type QCInspection = z.infer<typeof QCInspectionSchema>
