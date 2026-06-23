'use client'

import { use, useState } from 'react'
import { useRouter } from 'next/navigation'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Plus, Pencil, Trash2, FileText, Download } from 'lucide-react'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { PageHeader } from '@/components/ui/page-header'
import { CurrencyDisplay } from '@/components/ui/currency-display'
import { StatusBadge } from '@/components/ui/status-badge'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { MeasurementForm } from '@/components/measurements/MeasurementForm'
import { useUpdateMeasurementProfile, useDeleteMeasurementProfile } from '@/lib/api/hooks/useMeasurements'
import { format } from 'date-fns'

type Tab = 'orders' | 'measurements' | 'documents' | 'balance' | 'timeline'

interface DocumentRow {
  id: number
  kind?: string
  reference_type?: string
  reference_id?: number
  size_bytes?: number
  generated_at?: string
  download_url?: string
}

const DOC_KIND_LABELS: Record<string, string> = {
  job_card: 'Job Card',
  packing_slip: 'Packing Slip',
  gst_invoice: 'GST Invoice',
  measurement_card: 'Measurement Card',
  delivery_receipt: 'Delivery Receipt',
  report: 'Report',
}

interface MeasurementRow {
  id: number
  name?: string
  type?: string
  current_version?: { version_number?: number; status?: string; effective_from?: string } | null
}

interface CustomerDetail {
  id: number
  customer_code?: string
  name?: string
  phone?: string
  phone_masked?: string
  address?: string
  created_at?: string
  family_members?: Array<{ id: number; name?: string; relationship?: string; relation?: string }>
}

interface BalanceData {
  invoiced_paise?: number
  paid_paise?: number
  credited_paise?: number
  outstanding_paise?: number
  invoices?: Array<{ id: number; invoice_no?: string; status?: string; total_paise?: number; balance_paise?: number }>
}

interface OrderRow {
  id: number
  order_code?: string
  status?: string
  created_at?: string
  expected_delivery_date?: string
}

const TAB_LABELS: { key: Tab; label: string }[] = [
  { key: 'orders', label: 'Orders' },
  { key: 'measurements', label: 'Measurements' },
  { key: 'documents', label: 'Documents' },
  { key: 'balance', label: 'Balance' },
  { key: 'timeline', label: 'Timeline' },
]

export default function CustomerDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const customerId = parseInt(id)
  const router = useRouter()
  const [tab, setTab] = useState<Tab>('orders')

  const { data: customer, isLoading } = useQuery<CustomerDetail>({
    queryKey: ['customer', customerId],
    queryFn: () => apiGet<CustomerDetail>(ENDPOINTS.customer(customerId)).then((r) => r.data),
  })

  // Orders + balance load eagerly so the summary cards are always populated.
  const { data: orders = [] } = useQuery<OrderRow[]>({
    queryKey: ['customer', customerId, 'orders'],
    queryFn: () => apiGet<OrderRow[]>(ENDPOINTS.customerOrders(customerId)).then((r) => r.data),
  })

  const { data: balanceData } = useQuery<BalanceData>({
    queryKey: ['customer', customerId, 'balance'],
    queryFn: () => apiGet<BalanceData>(ENDPOINTS.customerBalance(customerId)).then((r) => r.data),
  })

  const { data: measurements = [] } = useQuery<MeasurementRow[]>({
    queryKey: ['customer', customerId, 'measurements'],
    queryFn: () => apiGet<MeasurementRow[]>(ENDPOINTS.measurements(customerId)).then((r) => r.data),
    enabled: tab === 'measurements',
  })

  const { data: timeline = [] } = useQuery<Array<{ id: number; event: string; created_at?: string }>>({
    queryKey: ['customer', customerId, 'timeline'],
    queryFn: () => apiGet<Array<{ id: number; event: string; created_at?: string }>>(ENDPOINTS.customerTimeline(customerId)).then((r) => r.data),
    enabled: tab === 'timeline',
  })

  const { data: documents = [] } = useQuery<DocumentRow[]>({
    queryKey: ['customer', customerId, 'documents'],
    queryFn: () => apiGet<DocumentRow[]>(ENDPOINTS.customerDocuments(customerId)).then((r) => r.data),
    enabled: tab === 'documents',
  })

  // Measurement CRUD state
  const queryClient = useQueryClient()
  const [showAddMeasurement, setShowAddMeasurement] = useState(false)
  const [renameTarget, setRenameTarget] = useState<MeasurementRow | null>(null)
  const [renameValue, setRenameValue] = useState('')
  const [deleteTarget, setDeleteTarget] = useState<MeasurementRow | null>(null)
  const updateProfile = useUpdateMeasurementProfile(customerId)
  const removeProfile = useDeleteMeasurementProfile(customerId)

  function refreshMeasurements() {
    queryClient.invalidateQueries({ queryKey: ['customer', customerId, 'measurements'] })
  }

  function startRename(m: MeasurementRow) {
    setRenameTarget(m)
    setRenameValue(m.name ?? '')
  }

  async function submitRename() {
    if (!renameTarget || !renameValue.trim()) return
    try {
      await updateProfile.mutateAsync({ profileId: renameTarget.id, data: { name: renameValue.trim() } })
      refreshMeasurements()
      setRenameTarget(null)
      toast.success('Measurement renamed')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed to rename')
    }
  }

  async function confirmDelete() {
    if (!deleteTarget) return
    try {
      await removeProfile.mutateAsync(deleteTarget.id)
      refreshMeasurements()
      setDeleteTarget(null)
      toast.success('Measurement deleted')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Cannot delete this measurement')
    }
  }

  if (isLoading) return <TableSkeleton rows={4} cols={4} />
  if (!customer) return <p className="text-sm text-[var(--color-text-muted)]">Customer not found</p>

  const totalSpent = (balanceData?.invoiced_paise ?? 0) / 100
  const outstanding = (balanceData?.outstanding_paise ?? 0) / 100

  return (
    <div className="space-y-6">
      <PageHeader title={customer.name ?? `Customer #${customerId}`} subtitle={customer.customer_code} />

      {/* Summary metrics */}
      <div className="grid grid-cols-3 gap-4">
        <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <p className="text-xs text-[var(--color-text-muted)] mb-1">Total Orders</p>
          <p className="text-2xl font-semibold tabular-nums">{orders.length}</p>
        </div>
        <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <p className="text-xs text-[var(--color-text-muted)] mb-1">Total Invoiced</p>
          <CurrencyDisplay amount={totalSpent} className="text-2xl font-semibold" />
        </div>
        <div className="rounded-xl border border-red-200 bg-red-50 p-4">
          <p className="text-xs text-red-600 mb-1">Outstanding</p>
          <CurrencyDisplay amount={outstanding} className="text-2xl font-semibold text-red-600" />
        </div>
      </div>

      {/* Contact details */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
        {[
          { label: 'Phone', value: customer.phone ?? customer.phone_masked ?? '—' },
          { label: 'Code', value: customer.customer_code ?? '—' },
          { label: 'Address', value: customer.address ?? '—' },
          {
            label: 'Customer Since',
            value: customer.created_at ? format(new Date(customer.created_at), 'dd MMM yyyy') : '—',
          },
        ].map(({ label, value }) => (
          <div key={label}>
            <p className="text-xs text-[var(--color-text-muted)]">{label}</p>
            <p className="font-medium text-[var(--color-text-primary)] mt-0.5 break-words">{value}</p>
          </div>
        ))}
      </div>

      {/* Family members */}
      {customer.family_members && customer.family_members.length > 0 && (
        <div className="flex items-center gap-2 flex-wrap">
          <span className="text-xs text-[var(--color-text-muted)]">Family:</span>
          {customer.family_members.map((m) => (
            <span key={m.id} className="px-2 py-0.5 text-xs bg-[var(--color-surface-alt)] rounded-full text-[var(--color-text-secondary)]">
              {m.name} ({m.relationship ?? m.relation})
            </span>
          ))}
        </div>
      )}

      {/* Tabs */}
      <div className="border-b border-[var(--color-border)]">
        <div className="flex gap-0">
          {TAB_LABELS.map(({ key, label }) => (
            <button
              key={key}
              onClick={() => setTab(key)}
              className={`px-4 py-2.5 text-sm font-medium border-b-2 transition-colors ${
                tab === key
                  ? 'border-[var(--color-brand)] text-[var(--color-brand)]'
                  : 'border-transparent text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'
              }`}
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      {/* Orders tab */}
      {tab === 'orders' && (
        <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
          <div className="divide-y divide-[var(--color-border)] bg-white">
            {orders.map((o) => (
              <div
                key={o.id}
                onClick={() => router.push(`/orders/${o.id}`)}
                className="flex items-center gap-4 px-4 py-3 cursor-pointer hover:bg-[var(--color-surface-alt)] transition-colors"
              >
                <span className="font-mono text-xs font-semibold text-[var(--color-brand)]">
                  {o.order_code}
                </span>
                <div className="flex-1" />
                <StatusBadge status={o.status ?? 'draft'} />
                <span className="text-xs text-[var(--color-text-muted)]">
                  {o.created_at ? format(new Date(o.created_at), 'dd MMM yy') : '—'}
                </span>
              </div>
            ))}
            {orders.length === 0 && (
              <div className="py-8 text-center text-sm text-[var(--color-text-muted)]">No orders</div>
            )}
          </div>
        </div>
      )}

      {/* Measurements tab */}
      {tab === 'measurements' && (
        <div className="space-y-3">
          <div className="flex justify-end">
            <button
              onClick={() => setShowAddMeasurement(true)}
              className="flex items-center gap-2 px-4 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-xl hover:bg-[var(--color-brand-dark)] transition-colors"
            >
              <Plus size={15} strokeWidth={1.75} /> Add measurement
            </button>
          </div>

          <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
            <div className="divide-y divide-[var(--color-border)] bg-white">
              {measurements.map((m) => (
                <div
                  key={m.id}
                  className="flex items-center gap-4 px-4 py-3 hover:bg-[var(--color-surface-alt)] transition-colors"
                >
                  <button
                    onClick={() => router.push(`/measurements/${m.id}`)}
                    className="flex flex-1 items-center gap-4 text-left cursor-pointer min-w-0"
                  >
                    <p className="flex-1 text-sm font-medium truncate">{m.name ?? 'Self'}</p>
                    {m.current_version?.version_number != null && (
                      <span className="text-xs text-[var(--color-text-muted)]">v{m.current_version.version_number}</span>
                    )}
                    {m.current_version?.status && <StatusBadge status={m.current_version.status} />}
                    <span className="text-xs text-[var(--color-text-muted)]">
                      {m.current_version?.effective_from ? format(new Date(m.current_version.effective_from), 'dd MMM yy') : '—'}
                    </span>
                  </button>
                  <button
                    onClick={() => startRename(m)}
                    title="Rename"
                    className="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--color-text-muted)] hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-text-primary)] transition-colors"
                  >
                    <Pencil size={14} strokeWidth={1.75} />
                  </button>
                  <button
                    onClick={() => setDeleteTarget(m)}
                    title="Delete"
                    className="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--color-text-muted)] hover:bg-red-50 hover:text-[var(--color-danger)] transition-colors"
                  >
                    <Trash2 size={14} strokeWidth={1.75} />
                  </button>
                </div>
              ))}
              {measurements.length === 0 && (
                <div className="py-8 text-center text-sm text-[var(--color-text-muted)]">No measurements</div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Documents tab */}
      {tab === 'documents' && (
        <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
          <div className="divide-y divide-[var(--color-border)] bg-white">
            {documents.map((d) => (
              <a
                key={d.id}
                href={d.download_url}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-4 px-4 py-3 hover:bg-[var(--color-surface-alt)] transition-colors"
              >
                <FileText size={16} strokeWidth={1.75} className="text-[var(--color-brand)] shrink-0" />
                <span className="flex-1 text-sm font-medium">
                  {d.kind ? (DOC_KIND_LABELS[d.kind] ?? d.kind) : 'Document'}
                  {d.reference_id != null && (
                    <span className="ml-2 text-xs text-[var(--color-text-muted)]">#{d.reference_id}</span>
                  )}
                </span>
                <span className="text-xs text-[var(--color-text-muted)]">
                  {d.generated_at ? format(new Date(d.generated_at), 'dd MMM yy') : '—'}
                </span>
                <Download size={15} strokeWidth={1.75} className="text-[var(--color-text-muted)] shrink-0" />
              </a>
            ))}
            {documents.length === 0 && (
              <div className="py-8 text-center text-sm text-[var(--color-text-muted)]">No documents yet</div>
            )}
          </div>
        </div>
      )}

      {/* Balance tab */}
      {tab === 'balance' && (
        <div className="space-y-4">
          <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 flex justify-between items-center">
            <p className="text-sm font-medium text-red-700">Total Outstanding</p>
            <CurrencyDisplay amount={outstanding} className="text-xl font-bold text-red-700" />
          </div>
          <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
            <div className="divide-y divide-[var(--color-border)] bg-white">
              {(balanceData?.invoices ?? []).map((inv) => (
                <div
                  key={inv.id}
                  onClick={() => router.push(`/finance/invoices/${inv.id}`)}
                  className="flex items-center gap-4 px-4 py-3 cursor-pointer hover:bg-[var(--color-surface-alt)] transition-colors"
                >
                  <span className="font-mono text-xs font-semibold">{inv.invoice_no}</span>
                  <div className="flex-1" />
                  <StatusBadge status={inv.status ?? 'issued'} />
                  <CurrencyDisplay amount={(inv.balance_paise ?? 0) / 100} className="text-sm font-semibold text-red-600" />
                </div>
              ))}
              {!balanceData?.invoices?.length && (
                <div className="py-8 text-center text-sm text-[var(--color-text-muted)]">No invoices</div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Timeline tab */}
      {tab === 'timeline' && (
        <div className="relative pl-4 space-y-0">
          <div className="absolute left-6 top-2 bottom-2 w-px bg-[var(--color-border)]" />
          {timeline.map((entry) => (
            <div key={entry.id} className="relative flex gap-4 pb-4">
              <div className="relative z-10 mt-1 w-3 h-3 rounded-full bg-[var(--color-brand)] ring-2 ring-white shrink-0" />
              <div>
                <p className="text-sm text-[var(--color-text-primary)]">{entry.event}</p>
                {entry.created_at && (
                  <p className="text-xs text-[var(--color-text-muted)]">
                    {format(new Date(entry.created_at), 'dd MMM yyyy HH:mm')}
                  </p>
                )}
              </div>
            </div>
          ))}
          {timeline.length === 0 && (
            <p className="py-8 text-center text-sm text-[var(--color-text-muted)]">No events</p>
          )}
        </div>
      )}

      {/* Add measurement drawer */}
      <DrawerPanel
        open={showAddMeasurement}
        onClose={() => setShowAddMeasurement(false)}
        title="New measurement"
        size="lg"
      >
        <div className="p-4">
          <MeasurementForm
            customerId={customerId}
            onCreated={() => {
              refreshMeasurements()
              setShowAddMeasurement(false)
            }}
            onCancel={() => setShowAddMeasurement(false)}
          />
        </div>
      </DrawerPanel>

      {/* Rename drawer */}
      <DrawerPanel
        open={renameTarget !== null}
        onClose={() => setRenameTarget(null)}
        title="Rename measurement"
        size="sm"
      >
        <div className="space-y-4 p-4">
          <input
            value={renameValue}
            onChange={(e) => setRenameValue(e.target.value)}
            onKeyDown={(e) => { if (e.key === 'Enter') submitRename() }}
            autoFocus
            placeholder="Measurement name"
            className="w-full h-10 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
          />
          <div className="flex justify-end gap-2">
            <button
              onClick={() => setRenameTarget(null)}
              className="px-4 h-10 rounded-lg border border-[var(--color-border)] text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Cancel
            </button>
            <button
              onClick={submitRename}
              disabled={!renameValue.trim() || updateProfile.isPending}
              className="px-5 h-10 rounded-lg bg-[var(--color-brand)] text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            >
              {updateProfile.isPending ? 'Saving…' : 'Save'}
            </button>
          </div>
        </div>
      </DrawerPanel>

      {/* Delete confirmation */}
      <ConfirmDialog
        open={deleteTarget !== null}
        onClose={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
        title="Delete this measurement?"
        description={`"${deleteTarget?.name ?? 'This measurement'}" will be removed. Measurements already used on an order cannot be deleted.`}
        variant="danger"
        confirmLabel="Delete"
        cancelLabel="Keep"
        loading={removeProfile.isPending}
      />
    </div>
  )
}
