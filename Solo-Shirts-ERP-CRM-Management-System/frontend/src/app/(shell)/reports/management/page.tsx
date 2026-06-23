'use client'

import { useState } from 'react'
import { format, subDays } from 'date-fns'
import { AlertTriangle, Loader2 } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { usePermission } from '@/lib/auth/permissions'
import { formatINR } from '@/lib/utils'
import {
  useReportDashboard, useDailyOrders, usePendingPayments, useProductionStages,
  useDamageReport, useSalesGst, useInventoryStockReport, usePurchasesReport,
  type ReportFilters,
} from '@/lib/api/hooks/useManagementReports'

const inr = (paise: number) => formatINR((paise ?? 0) / 100)

const TABS = [
  { id: 'dashboard', label: 'Overview' },
  { id: 'orders', label: 'Daily Orders' },
  { id: 'payments', label: 'Pending Payments' },
  { id: 'production', label: 'Production' },
  { id: 'damage', label: 'Damage / Waste' },
  { id: 'inventory', label: 'Inventory' },
  { id: 'purchases', label: 'Purchases' },
  { id: 'sales-gst', label: 'Sales / GST' },
] as const
type TabId = (typeof TABS)[number]['id']

const DATED_TABS: TabId[] = ['dashboard', 'orders', 'damage', 'purchases', 'sales-gst']

export default function ManagementReportsPage() {
  const { can } = usePermission()
  const [tab, setTab] = useState<TabId>('dashboard')
  const [from, setFrom] = useState(format(subDays(new Date(), 29), 'yyyy-MM-dd'))
  const [to, setTo] = useState(format(new Date(), 'yyyy-MM-dd'))
  const filters: ReportFilters = { from, to }

  if (!can('reports.view')) {
    return (
      <div className="flex flex-col items-center justify-center h-64 gap-3 text-center">
        <p className="text-lg font-semibold text-red-600">Access Denied</p>
        <p className="text-sm text-[var(--color-text-muted)]">You do not have permission to view management reports.</p>
      </div>
    )
  }

  return (
    <div className="space-y-5">
      <PageHeader title="Management Reports" subtitle="Read-only business overview · branch-scoped" />

      <div className="flex flex-wrap items-end gap-3">
        {DATED_TABS.includes(tab) && (
          <>
            <label className="text-xs text-[var(--color-text-muted)]">From
              <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="mt-1 block h-9 px-2 text-sm border border-[var(--color-border)] rounded-lg" />
            </label>
            <label className="text-xs text-[var(--color-text-muted)]">To
              <input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="mt-1 block h-9 px-2 text-sm border border-[var(--color-border)] rounded-lg" />
            </label>
          </>
        )}
      </div>

      <div className="flex flex-wrap gap-1.5 border-b border-[var(--color-border)] pb-2">
        {TABS.map((t) => (
          <button
            key={t.id}
            onClick={() => setTab(t.id)}
            className={tab === t.id
              ? 'rounded-lg bg-[var(--color-brand)] px-3 h-8 text-xs font-medium text-white'
              : 'rounded-lg border border-[var(--color-border-mid)] px-3 h-8 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]'}
          >
            {t.label}
          </button>
        ))}
      </div>

      {tab === 'dashboard' && <DashboardTab filters={filters} />}
      {tab === 'orders' && <OrdersTab filters={filters} />}
      {tab === 'payments' && <PaymentsTab />}
      {tab === 'production' && <ProductionTab />}
      {tab === 'damage' && <DamageTab filters={filters} />}
      {tab === 'inventory' && <InventoryTab />}
      {tab === 'purchases' && <PurchasesTab filters={filters} />}
      {tab === 'sales-gst' && <SalesGstTab filters={filters} />}
    </div>
  )
}

// ── shared UI ────────────────────────────────────────────────────────────────

function QueryState({ q }: { q: { isLoading: boolean; isError: boolean; error: unknown } }) {
  if (q.isLoading) return <div className="flex items-center gap-2 py-12 justify-center text-sm text-[var(--color-text-muted)]"><Loader2 size={16} className="animate-spin" /> Loading…</div>
  const e = q.error as { message?: string; request_id?: string } | null
  return (
    <div className="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <AlertTriangle size={16} strokeWidth={1.75} className="mt-0.5 shrink-0" />
      <div><p>{e?.message ?? 'Could not load the report.'}</p>{e?.request_id && <p className="text-xs opacity-75">request_id: {e.request_id}</p>}</div>
    </div>
  )
}

function Stat({ label, value, tone }: { label: string; value: string | number; tone?: 'danger' | 'success' | 'brand' }) {
  const c = tone === 'danger' ? 'text-red-600' : tone === 'success' ? 'text-green-700' : tone === 'brand' ? 'text-[var(--color-brand)]' : 'text-[var(--color-text-primary)]'
  return (
    <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
      <p className="text-xs text-[var(--color-text-muted)] mb-1">{label}</p>
      <p className={`text-2xl font-semibold tabular-nums ${c}`}>{value}</p>
    </div>
  )
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-2">
      <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">{title}</h3>
      {children}
    </section>
  )
}

function SimpleTable({ head, rows, empty = 'No data.' }: { head: string[]; rows: (string | number)[][]; empty?: string }) {
  if (rows.length === 0) return <p className="text-sm text-[var(--color-text-muted)] py-6 text-center rounded-xl border border-[var(--color-border)]">{empty}</p>
  return (
    <div className="rounded-xl border border-[var(--color-border)] overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-[var(--color-border)] text-left text-[11px] uppercase tracking-wide text-[var(--color-text-muted)]">
            {head.map((h, i) => <th key={i} className={`px-3 py-2 font-medium ${i > 0 ? 'text-right' : ''}`}>{h}</th>)}
          </tr>
        </thead>
        <tbody>
          {rows.map((r, ri) => (
            <tr key={ri} className="border-b border-[var(--color-border)] last:border-0">
              {r.map((c, ci) => <td key={ci} className={`px-3 py-2 ${ci > 0 ? 'text-right font-mono tabular-nums' : ''}`}>{c}</td>)}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

// ── tabs ─────────────────────────────────────────────────────────────────────

function DashboardTab({ filters }: { filters: ReportFilters }) {
  const q = useReportDashboard(filters)
  if (!q.data) return <QueryState q={q} />
  const d = q.data
  return (
    <div className="space-y-6">
      <Section title="Orders">
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <Stat label="Total" value={d.orders.total_orders} />
          <Stat label="Confirmed" value={d.orders.confirmed_orders} tone="brand" />
          <Stat label="Delivered" value={d.orders.delivered_orders} tone="success" />
          <Stat label="Cancelled" value={d.orders.cancelled_orders} tone="danger" />
        </div>
      </Section>
      <Section title="Payments">
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <Stat label="Invoiced (range)" value={inr(d.payments.invoiced_paise)} />
          <Stat label="Collected (range)" value={inr(d.payments.paid_paise)} tone="success" />
          <Stat label="Outstanding (now)" value={inr(d.payments.pending_paise)} tone="danger" />
        </div>
      </Section>
      <Section title="Production pipeline">
        <SimpleTable head={['Stage', 'Items']} rows={Object.entries(d.production).map(([s, c]) => [s.replace(/_/g, ' '), c])} />
      </Section>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Section title="Inventory">
          <div className="grid grid-cols-2 gap-3">
            <Stat label="Active rolls" value={d.inventory.fabric_rolls_count} />
            <Stat label="Low stock" value={d.inventory.low_stock_count} tone="danger" />
            <Stat label="Available (m)" value={d.inventory.available_total} tone="success" />
            <Stat label="Reserved (m)" value={d.inventory.reserved_total} tone="brand" />
          </div>
        </Section>
        <Section title="Damage & purchases">
          <div className="grid grid-cols-2 gap-3">
            <Stat label="Damage reports" value={d.damage.reported_count} />
            <Stat label="Lost (m)" value={d.damage.reported_quantity} tone="danger" />
            <Stat label="POs" value={d.purchases.purchase_orders_count} />
            <Stat label="Purchase total" value={inr(d.purchases.purchase_total_paise)} />
          </div>
        </Section>
      </div>
    </div>
  )
}

function OrdersTab({ filters }: { filters: ReportFilters }) {
  const q = useDailyOrders(filters)
  if (!q.data) return <QueryState q={q} />
  return <SimpleTable head={['Date', 'Orders', 'Items', 'Delivered', 'Cancelled', 'Rush']} empty="No orders in this range." rows={q.data.rows.map((r) => [r.date, r.orders_count, r.items_count, r.delivered_count, r.cancelled_count, r.rush_count])} />
}

function PaymentsTab() {
  const q = usePendingPayments({})
  if (!q.data) return <QueryState q={q} />
  return <SimpleTable head={['Invoice', 'Order', 'Customer', 'Total', 'Paid', 'Balance', 'Days']} empty="No pending payments." rows={q.data.rows.map((r) => [r.invoice_no, r.order_code ?? '—', r.customer_name ?? '—', inr(r.invoice_total_paise), inr(r.paid_paise), inr(r.balance_paise), r.days_pending ?? '—'])} />
}

function ProductionTab() {
  const q = useProductionStages({})
  if (!q.data) return <QueryState q={q} />
  return <SimpleTable head={['Stage', 'Items', 'Due today', 'Overdue', 'Rush']} rows={q.data.rows.map((r) => [r.stage.replace(/_/g, ' '), r.count, r.due_today, r.overdue, r.rush])} />
}

function DamageTab({ filters }: { filters: ReportFilters }) {
  const q = useDamageReport(filters)
  if (!q.data) return <QueryState q={q} />
  const d = q.data
  return (
    <div className="space-y-6">
      <div className="grid grid-cols-2 gap-3">
        <Stat label="Reports" value={d.totals.count} />
        <Stat label="Total lost (m)" value={d.totals.quantity} tone="danger" />
      </div>
      <Section title="By stage"><SimpleTable head={['Stage', 'Count', 'Metres']} rows={d.by_stage.map((r) => [r.key, r.count, r.quantity])} /></Section>
      <Section title="By type"><SimpleTable head={['Type', 'Count', 'Metres']} rows={d.by_type.map((r) => [r.key.replace(/_/g, ' '), r.count, r.quantity])} /></Section>
      <Section title="By status"><SimpleTable head={['Status', 'Count', 'Metres']} rows={d.by_status.map((r) => [r.key, r.count, r.quantity])} /></Section>
    </div>
  )
}

function InventoryTab() {
  const q = useInventoryStockReport({})
  if (!q.data) return <QueryState q={q} />
  const d = q.data
  return (
    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <Stat label="Active rolls" value={d.fabric_rolls_count} />
      <Stat label="Low stock" value={d.low_stock_count} tone="danger" />
      <Stat label="Remaining (m)" value={d.remaining_total} />
      <Stat label="Available (m)" value={d.available_total} tone="success" />
      <Stat label="Reserved (m)" value={d.reserved_total} tone="brand" />
      <Stat label="Consumed (m)" value={d.consumed_total} />
      <Stat label="Damaged (m)" value={d.damaged_total} tone="danger" />
    </div>
  )
}

function PurchasesTab({ filters }: { filters: ReportFilters }) {
  const q = usePurchasesReport(filters)
  if (!q.data) return <QueryState q={q} />
  const d = q.data
  return (
    <div className="space-y-6">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <Stat label="POs" value={d.purchase_orders_count} />
        <Stat label="Placed" value={d.placed_count} tone="brand" />
        <Stat label="Received" value={d.received_count} tone="success" />
        <Stat label="Cancelled" value={d.cancelled_count} tone="danger" />
        <Stat label="Purchase total" value={inr(d.purchase_total_paise)} />
        <Stat label="Received (m)" value={d.received_metres} />
      </div>
      <Section title="By supplier"><SimpleTable head={['Supplier', 'Orders', 'Total']} empty="No purchases in this range." rows={(d.by_supplier ?? []).map((r) => [r.supplier, r.orders, inr(r.total_paise)])} /></Section>
    </div>
  )
}

function SalesGstTab({ filters }: { filters: ReportFilters }) {
  const q = useSalesGst(filters)
  if (!q.data) return <QueryState q={q} />
  const d = q.data
  return (
    <div className="space-y-6">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <Stat label="Invoices" value={d.invoice_count} />
        <Stat label="Taxable" value={inr(d.taxable_paise)} />
        <Stat label="CGST" value={inr(d.cgst_paise)} />
        <Stat label="SGST" value={inr(d.sgst_paise)} />
        <Stat label="IGST" value={inr(d.igst_paise)} />
        <Stat label="Total" value={inr(d.total_paise)} tone="brand" />
        <Stat label="Paid" value={inr(d.paid_paise)} tone="success" />
        <Stat label="Balance" value={inr(d.balance_paise)} tone="danger" />
      </div>
      <Section title="By GST rate"><SimpleTable head={['Rate %', 'Taxable', 'Tax']} rows={d.by_rate.map((r) => [r.gst_rate, inr(r.taxable_paise), inr(r.tax_paise)])} /></Section>
    </div>
  )
}
