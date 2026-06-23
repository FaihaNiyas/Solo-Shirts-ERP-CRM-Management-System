// Front Desk wizard — Phase 1 type definitions.
// Each SubOrderDraft represents ONE physical shirt (quantity is always 1).
// PDF / Print fields are Phase-1 placeholders surfaced as "Pending"
// and wired to real APIs in Phase 2.

import type { Customer } from '@/lib/api/schemas/customers'

export type OrderSource = 'walk_in' | 'phone' | 'whatsapp' | 'online'
export type DeliveryMode = 'pickup' | 'home' | 'courier'
export type PaymentMethod = 'cash' | 'upi' | 'bank_transfer'

export const WIZARD_STEPS = ['customer', 'member', 'main', 'subOrders', 'print', 'payment', 'review'] as const
export type WizardStep = (typeof WIZARD_STEPS)[number]

export const STEP_LABELS: Record<WizardStep, string> = {
  customer: 'Customer',
  member: 'Member',
  main: 'Order Details',
  subOrders: 'Sub-Orders',
  print: 'Print',
  payment: 'Pricing & Payment',
  review: 'Review & Confirm',
}

export type PdfStatus = 'pending' | 'generated' | 'failed'
export type PrintStatus = 'pending' | 'printed'

export const SOURCE_LABELS: Record<OrderSource, string> = {
  walk_in: 'Walk-in',
  phone: 'Phone',
  whatsapp: 'WhatsApp',
  online: 'Online',
}

export const DELIVERY_MODE_LABELS: Record<DeliveryMode, string> = {
  pickup: 'Pickup',
  home: 'Home delivery',
  courier: 'Courier',
}

export const PAYMENT_METHOD_LABELS: Record<PaymentMethod, string> = {
  cash: 'Cash',
  upi: 'UPI',
  bank_transfer: 'Bank Transfer',
}

/** A selectable catalog entry (fabric / style / fit). */
export interface CatalogOption {
  id: string
  label: string
}

export interface MainOrderMeta {
  source: OrderSource
  orderDate: string // yyyy-mm-dd
  deliveryDate: string // yyyy-mm-dd → expected_delivery_date
  deliveryMode: DeliveryMode
  notes: string
  totalShirts: number
}

export interface SubOrderDraft {
  tempId: string
  // The persisted order_item id, set once the order is created (Print Center).
  itemId: number | null
  // Product type for this sub-order (1 sub-order = 1 shirt OR 1 trouser).
  productType?: 'shirt' | 'trouser'
  // Measurement (per shirt/trouser)
  measurementVersionId: number | null
  measurementLabel: string | null
  measurementStatus: string | null // informational only — no approval gate
  // Catalog selections (per shirt)
  fabricId: string | null
  fabricLabel: string | null
  styleId: string | null
  styleLabel: string | null
  fitId: string | null
  fitLabel: string | null
  quantity: 1
  notes: string
  // --- Phase 3C: per-shirt pricing (rupees; gstRate one of 0/5/12/18) ---
  basePrice: number
  discountAmount: number
  gstRate: number
  // --- Phase 2: PDF/print ---
  pdfStatus: PdfStatus
  documentId: number | null
  pdfUrl: string | null
  printStatus: PrintStatus
  productionStatus: 'draft'
}

export interface PaymentDraft {
  // Phase 3C: the order total is derived from per-shirt pricing, not entered.
  advancePaid: number
  method: PaymentMethod | null
  reference: string
}

/** Serialisable snapshot persisted to localStorage (Phase 2: server-side). */
export interface WizardSnapshot {
  version: 1
  activeStep: WizardStep
  orderId: number | null
  // Phase 6B — server draft id once this draft is persisted server-side.
  draftId?: number | null
  customer: Customer | null
  memberId: number | null
  memberLabel: string
  meta: MainOrderMeta
  subOrders: SubOrderDraft[]
  payment: PaymentDraft
  savedAt: string | null
}
