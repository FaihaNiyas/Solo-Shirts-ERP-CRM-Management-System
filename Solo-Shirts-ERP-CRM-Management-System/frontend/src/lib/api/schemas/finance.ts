import { z } from 'zod'

// FE-025: aligned to the backend finance resources. Real backend fields are
// modelled; fields the FE displays but the backend doesn't return are kept
// optional (backend gaps). Over-strict enums are relaxed to strings so a valid
// backend value never falsely fails validation.

// InvoiceResource
export const InvoiceSchema = z.object({
  id: z.number(),
  invoice_no: z.string().optional(),
  invoice_number: z.string().optional(), // backend alias of invoice_no
  order_id: z.number(),
  customer_id: z.number(),
  customer_name: z.string().nullable().optional(),
  gst_treatment: z.string().nullable().optional(),
  subtotal_paise: z.number().optional(),
  cgst_paise: z.number().optional(),
  sgst_paise: z.number().optional(),
  igst_paise: z.number().optional(),
  delivery_charges_paise: z.number().optional(),
  discount_paise: z.number().optional(),
  total_paise: z.number().optional(),
  total_amount: z.number().optional(),
  paid_amount: z.number().optional(),
  balance_amount: z.number().optional(),
  status: z.string(),
  issued_at: z.string().nullable().optional(),
  created_at: z.string().optional(),
  pdf_path: z.string().nullable().optional(),
  lines: z.array(z.unknown()).optional(),
  // FE-only / backend gaps (optional so the UI compiles):
  branch_id: z.number().optional(),
  order_number: z.string().optional(),
  subtotal: z.number().optional(),
  tax_amount: z.number().optional(),
  discount_amount: z.number().optional(),
  delivery_charge: z.number().optional(),
  gst_type: z.string().optional(),
  due_date: z.string().nullable().optional(),
  created_by: z.number().optional(),
  updated_at: z.string().optional(),
})

// PaymentResource
export const PaymentSchema = z.object({
  id: z.number(),
  invoice_id: z.number(),
  method: z.string(),
  amount_paise: z.number().optional(),
  reference_no: z.string().nullable().optional(),
  bank_account_last4: z.string().nullable().optional(),
  paid_at: z.string().nullable().optional(),
  recorded_by: z.number().optional(),
  // FE-only aliases (optional):
  amount: z.number().optional(),
  reference: z.string().nullable().optional(),
  notes: z.string().nullable().optional(),
  recorded_at: z.string().optional(),
  created_at: z.string().optional(),
})

// CreditNoteResource
export const CreditNoteSchema = z.object({
  id: z.number(),
  credit_no: z.string().optional(),
  invoice_id: z.number(),
  reason: z.string(),
  total_paise: z.number().optional(),
  issued_at: z.string().nullable().optional(),
  issued_by: z.number().optional(),
  // FE-only aliases (optional):
  credit_note_number: z.string().optional(),
  amount: z.number().optional(),
  created_by: z.number().optional(),
  created_at: z.string().optional(),
})

// FinanceDashboardController::summary → { invoice_count, invoiced_paise,
// collected_paise, credited_paise, outstanding_paise }. FE-only display fields
// kept optional.
export const FinanceSummarySchema = z.object({
  invoice_count: z.number().optional(),
  invoiced_paise: z.number().optional(),
  collected_paise: z.number().optional(),
  credited_paise: z.number().optional(),
  outstanding_paise: z.number().optional(),
  // FE-only / other-dashboard fields (optional):
  total_revenue: z.number().optional(),
  outstanding_amount: z.number().optional(),
  orders_today: z.number().optional(),
  deliveries_pending: z.number().optional(),
  production_in_progress: z.number().optional(),
  low_stock_alerts: z.number().optional(),
  qc_pending: z.number().optional(),
  completed_today: z.number().optional(),
})

export type Invoice = z.infer<typeof InvoiceSchema>
export type Payment = z.infer<typeof PaymentSchema>
export type CreditNote = z.infer<typeof CreditNoteSchema>
export type FinanceSummary = z.infer<typeof FinanceSummarySchema>
