'use client'

import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'

// Phase 9 — read-only management report hooks. All money fields are paise
// (divide by 100 before formatINR). Branch is enforced server-side from the
// auth context; only an Owner may pass branch_id.

export interface ReportFilters {
  from?: string
  to?: string
  branch_id?: number
  [key: string]: unknown
}

export interface DashboardReport {
  date_range: { from: string; to: string }
  branch_id: number | null
  orders: { total_orders: number; confirmed_orders: number; cancelled_orders: number; delivered_orders: number }
  payments: { invoiced_paise: number; paid_paise: number; pending_paise: number }
  production: Record<string, number>
  inventory: InventoryStockReport
  damage: { reported_count: number; reported_quantity: string; approved_quantity: string }
  purchases: PurchasesReport
}

export interface DailyOrderRow { date: string; orders_count: number; cancelled_count: number; items_count: number; delivered_count: number; rush_count: number }
export interface PendingPaymentRow { invoice_no: string; order_code: string | null; customer_name: string | null; customer_phone: string | null; invoice_total_paise: number; paid_paise: number; balance_paise: number; due_date: string | null; days_pending: number | null }
export interface ProductionStageRow { stage: string; count: number; due_today: number; overdue: number; rush: number }
export interface DamageGroupRow { key: string; count: number; quantity: string }
export interface DamageRecentRow { id: number; stage: string; damage_type: string; status: string; quantity: string; order_code: string | null; item_code: string | null; reported_at: string | null }
export interface DamageReportData { totals: { count: number; quantity: string }; by_status: DamageGroupRow[]; by_stage: DamageGroupRow[]; by_type: DamageGroupRow[]; recent: DamageRecentRow[] }
export interface SalesGstReport { invoice_count: number; taxable_paise: number; cgst_paise: number; sgst_paise: number; igst_paise: number; total_paise: number; paid_paise: number; balance_paise: number; by_rate: { gst_rate: number; taxable_paise: number; tax_paise: number }[] }
export interface InventoryStockReport { fabric_rolls_count: number; low_stock_count: number; remaining_total: string; available_total: string; reserved_total: string; consumed_total: string; damaged_total: string }
export interface PurchasesReport { purchase_orders_count: number; placed_count: number; received_count: number; cancelled_count: number; purchase_total_paise: number; received_metres: string; by_supplier?: { supplier: string; orders: number; total_paise: number }[] }

function useReport<T>(key: string, url: string, filters: ReportFilters, enabled = true) {
  return useQuery({
    queryKey: ['mreport', key, filters],
    queryFn: () => apiGet<T>(url, filters),
    select: (res) => res.data,
    staleTime: 30_000,
    enabled,
  })
}

export const useReportDashboard = (f: ReportFilters, enabled?: boolean) => useReport<DashboardReport>('dashboard', ENDPOINTS.reportDashboard, f, enabled)
export const useDailyOrders = (f: ReportFilters, enabled?: boolean) => useReport<{ date_range: { from: string; to: string }; rows: DailyOrderRow[] }>('orders-daily', ENDPOINTS.reportOrdersDaily, f, enabled)
export const usePendingPayments = (f: ReportFilters, enabled?: boolean) => useReport<{ rows: PendingPaymentRow[] }>('payments-pending', ENDPOINTS.reportPaymentsPending, f, enabled)
export const useProductionStages = (f: ReportFilters, enabled?: boolean) => useReport<{ rows: ProductionStageRow[] }>('production-stages', ENDPOINTS.reportProductionStages, f, enabled)
export const useDamageReport = (f: ReportFilters, enabled?: boolean) => useReport<DamageReportData>('damage', ENDPOINTS.reportDamage, f, enabled)
export const useSalesGst = (f: ReportFilters, enabled?: boolean) => useReport<SalesGstReport>('sales-gst', ENDPOINTS.reportSalesGst, f, enabled)
export const useInventoryStockReport = (f: ReportFilters, enabled?: boolean) => useReport<InventoryStockReport>('inventory-stock', ENDPOINTS.reportInventoryStock, f, enabled)
export const usePurchasesReport = (f: ReportFilters, enabled?: boolean) => useReport<PurchasesReport>('purchases', ENDPOINTS.reportPurchases, f, enabled)
