'use client'

import { use, useState } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { useRouter } from 'next/navigation'
import { Printer, Phone, Copy, Pencil, Wallet, Ruler } from 'lucide-react'
import { toast } from 'sonner'
import { useOrder, useCancelOrder } from '@/lib/api/hooks/useOrders'
import { PageHeader } from '@/components/ui/page-header'
import { StatusBadge } from '@/components/ui/status-badge'
import { OrderPaymentsSection } from '@/components/orders/OrderPaymentsSection'
import { PickupHandoverPanel } from '@/components/orders/PickupHandoverPanel'
import { OrderNotificationsSection } from '@/components/orders/OrderNotificationsSection'
import { OrderAlterationsSection } from '@/components/orders/OrderAlterationsSection'
import type { WhatsappEvent } from '@/lib/api/hooks/useWhatsapp'
import { ProductionBadge } from '@/components/ui/status-badge'
import { CurrencyDisplay } from '@/components/ui/currency-display'
import { InfoGrid } from '@/components/ui/info-grid'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { PickupItemModal } from '@/components/orders/PickupItemModal'
import { EditOrderModal } from '@/components/orders/EditOrderModal'
import { OrderPickupHistory } from '@/components/orders/OrderPickupHistory'
import { OrderDocuments } from '@/components/orders/OrderDocuments'
import { MeasurementViewModal } from '@/components/orders/MeasurementViewModal'
import { OrderTimeline } from '@/components/orders/OrderTimeline'
import { usePermission } from '@/lib/auth/permissions'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { apiGet } from '@/lib/api/client'
import { format } from 'date-fns'

const DELIVERY_MODE_LABELS: Record<string, string> = {
  pickup: 'Counter Pickup',
  home: 'Home Delivery',
  courier: 'Courier',
}

const SOURCE_LABELS: Record<string, string> = {
  walk_in: 'Walk-in',
  phone: 'Phone',
  whatsapp: 'WhatsApp',
  online: 'Online',
}

function deliveryModeLabel(mode: string): string {
  return DELIVERY_MODE_LABELS[mode] ?? mode
}

export default function OrderDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const orderId = parseInt(id)
  const router = useRouter()
  const { data: order, isLoading } = useOrder(orderId)
  const cancelMutation = useCancelOrder(orderId)
  const [cancelOpen, setCancelOpen] = useState(false)
  const [pickupItem, setPickupItem] = useState<{ id: number; item_code: string } | null>(null)
  const [jobCardBusy, setJobCardBusy] = useState<number | null>(null)
  const [editOpen, setEditOpen] = useState(false)
  const [measureItem, setMeasureItem] = useState<{ versionId: number; item_code: string } | null>(null)
  const [printingAll, setPrintingAll] = useState(false)
  const qc = useQueryClient()
  const { can } = usePermission()
  // V1: pay-now pickup requires both collecting payment and handing over.
  const canPickup = can('orders.handover') && can('orders.collect_payment')
  const canPrintJobCard = can('orders.print_job_card')
  const canEdit = can('orders.update')
  const canCollect = can('orders.collect_payment')

  // Generate (or reuse) a sub-order's job-card PDF on demand and open it. Uses
  // the authenticated API which returns a public signed URL — so it works even
  // if the PDF was never generated at intake, and never 401s like a raw
  // window.open of the API route would.
  async function printItemJobCard(itemId: number) {
    setJobCardBusy(itemId)
    try {
      const res = await apiGet<{ download_url?: string; url?: string }>(ENDPOINTS.itemJobCard(orderId, itemId))
      const url = res.data.download_url ?? res.data.url
      if (url) window.open(url, '_blank', 'noopener')
      else toast.error('Job card URL unavailable.')
    } catch (err) {
      toast.error((err as { message?: string })?.message ?? 'Could not generate the job card.')
    } finally {
      setJobCardBusy(null)
    }
  }

  // Generate every sub-order's job card in one go. Each is filed as a document,
  // so rather than spawning N popups (which the browser blocks) we refresh the
  // Documents section below and point the user there.
  async function printAllJobCards(itemIds: number[]) {
    if (itemIds.length === 0) return
    setPrintingAll(true)
    let ok = 0
    for (const id of itemIds) {
      try {
        await apiGet(ENDPOINTS.itemJobCard(orderId, id))
        ok++
      } catch {
        // skip a failed item; the rest still generate
      }
    }
    setPrintingAll(false)
    qc.invalidateQueries({ queryKey: ['orders', orderId, 'documents'] })
    toast.success(`${ok} job card${ok === 1 ? '' : 's'} ready — see Documents below`)
  }

  async function handleCancel(reason: string) {
    try {
      await cancelMutation.mutateAsync(reason)
      setCancelOpen(false)
      toast.success('Order cancelled')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed to cancel')
    }
  }

  if (isLoading) return <TableSkeleton rows={6} cols={4} />
  if (!order) return <p className="text-sm text-[var(--color-text-muted)]">Order not found</p>

  const items = order.items ?? []

  // Map the derived aggregate status → a StatusBadge colour variant.
  const AGG_VARIANT: Record<string, string> = {
    draft: 'draft',
    in_production: 'info',
    partially_ready: 'pending',
    ready: 'active',
    partially_delivered: 'pending',
    delivered: 'complete',
    cancelled: 'inactive',
  }
  const statusVariant = AGG_VARIANT[order.progress?.aggregate_status ?? ''] ?? 'neutral'

  // WhatsApp event types available for this order's state.
  const allDelivered = items.length > 0 && items.every((i) => i.state === 'delivered')
  const anyReady = items.some((i) => i.state === 'ready_for_delivery')
  const confirmed = order.lifecycle_status === 'order_received'
  const waEvents = Array.from(
    new Set<WhatsappEvent>([
      ...(allDelivered ? (['order_delivered'] as WhatsappEvent[]) : []),
      ...(anyReady ? (['order_ready_for_pickup'] as WhatsappEvent[]) : []),
      ...(confirmed ? (['order_confirmed'] as WhatsappEvent[]) : []),
      'payment_balance_reminder',
      'delivery_rescheduled',
    ]),
  )

  return (
    <div className="space-y-6">
      <PageHeader
        title={order.order_code ?? `Order #${orderId}`}
        subtitle={order.customer_name ?? `Customer #${order.customer_id}`}
        actions={
          <div className="flex flex-wrap gap-2">
            {canCollect && (order.balance_due ?? 0) > 0 && (
              <button
                onClick={() => document.getElementById('order-payments')?.scrollIntoView({ behavior: 'smooth' })}
                className="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium bg-[var(--color-brand)] text-white rounded-lg hover:bg-[var(--color-brand-dark)] transition-colors"
              >
                <Wallet size={15} strokeWidth={1.75} /> Collect Payment
              </button>
            )}
            {canEdit && (
              <button
                onClick={() => setEditOpen(true)}
                className="inline-flex items-center gap-1.5 px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
              >
                <Pencil size={15} strokeWidth={1.75} /> Edit
              </button>
            )}
            <button
              onClick={() => { void navigator.clipboard.writeText(order.order_code ?? ''); toast.success('Order code copied') }}
              title="Copy order code"
              className="inline-flex items-center gap-1.5 px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              <Copy size={15} strokeWidth={1.75} /> Copy
            </button>
            <button
              onClick={() => setCancelOpen(true)}
              className="px-3 py-2 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition-colors"
            >
              Cancel Order
            </button>
          </div>
        }
      />

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <p className="text-xs text-[var(--color-text-muted)] mb-1">Items</p>
          <p className="text-2xl font-semibold text-[var(--color-text-primary)]">{items.length}</p>
        </div>
        <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <p className="text-xs text-[var(--color-text-muted)] mb-1">Total</p>
          <CurrencyDisplay amount={order.total_amount ?? 0} className="text-2xl font-semibold" />
        </div>
        <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <p className="text-xs text-[var(--color-text-muted)] mb-1">Balance Due</p>
          <CurrencyDisplay amount={(order as Record<string, unknown>).balance_due as number ?? 0} className="text-2xl font-semibold" />
        </div>
        <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <p className="text-xs text-[var(--color-text-muted)] mb-1">Delivery</p>
          <p className="text-sm font-medium text-[var(--color-text-primary)]">
            {order.expected_delivery_date ? format(new Date(order.expected_delivery_date), 'dd MMM yyyy') : '—'}
          </p>
          {order.delivery_mode && (
            <p className="text-xs text-[var(--color-text-muted)] mt-0.5">{deliveryModeLabel(order.delivery_mode)}</p>
          )}
        </div>
      </div>

      {/* Production progress rollup (derived server-side from item states). Kept
          visually separate from payment status. */}
      {order.progress && (
        <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <div className="flex items-center gap-2">
              <span className="text-xs text-[var(--color-text-muted)] uppercase tracking-wide">Order Progress</span>
              <StatusBadge status={statusVariant} label={order.progress.aggregate_status_label} />
            </div>
            <span className="text-sm text-[var(--color-text-secondary)]">{order.progress.summary_label}</span>
          </div>
          <div className="mt-3 grid grid-cols-2 sm:grid-cols-5 gap-2 text-center">
            {([
              ['Total', order.progress.progress.total],
              ['Ready', order.progress.progress.ready],
              ['In Production', order.progress.progress.in_production],
              ['Delivered', order.progress.progress.delivered],
              ['Cancelled', order.progress.progress.cancelled],
            ] as const).map(([label, n]) => (
              <div key={label} className="rounded-lg bg-[var(--color-surface-alt)] py-2">
                <p className="text-lg font-semibold tabular-nums text-[var(--color-text-primary)]">{n}</p>
                <p className="text-[11px] text-[var(--color-text-muted)]">{label}</p>
              </div>
            ))}
          </div>
        </div>
      )}

      <InfoGrid
        items={[
          { label: 'Branch (took the order)', value: order.branch_name ?? '—' },
          {
            label: 'Customer',
            value: (
              <span className="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <span>{order.customer_name ?? `#${order.customer_id}`}</span>
                {order.customer_phone ? (
                  <a href={`tel:${order.customer_phone}`} className="inline-flex items-center gap-1 text-xs text-[var(--color-brand)] hover:underline">
                    <Phone size={11} strokeWidth={1.75} /> {order.customer_phone}
                  </a>
                ) : order.customer_phone_masked ? (
                  <span className="text-xs text-[var(--color-text-muted)]">{order.customer_phone_masked}</span>
                ) : null}
              </span>
            ),
          },
          { label: 'Source', value: SOURCE_LABELS[order.source ?? ''] ?? order.source ?? '—' },
          { label: 'Created', value: order.created_at ? format(new Date(order.created_at), 'dd MMM yyyy, HH:mm') : '—' },
          ...(order.notes ? [{ label: 'Notes', value: order.notes }] : []),
        ]}
      />

      {/* Per-sub-order table — each item carries its own production status (the
          single source of truth), box, ready-rack slot and delivery state. */}
      <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
        <div className="px-4 py-3 bg-[var(--color-surface-alt)] border-b border-[var(--color-border)] flex items-center justify-between gap-2">
          <p className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">
            Order Items
          </p>
          {canPrintJobCard && items.length > 1 && (
            <button
              onClick={() => printAllJobCards(items.map((it) => it.id))}
              disabled={printingAll}
              className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] bg-white px-3 py-1 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-50"
            >
              <Printer size={13} strokeWidth={1.75} /> {printingAll ? 'Generating…' : 'Print all job cards'}
            </button>
          )}
        </div>
        <div className="overflow-x-auto bg-white">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-[var(--color-border)] text-left text-[11px] uppercase tracking-wide text-[var(--color-text-muted)]">
                <th className="px-4 py-2 font-semibold">Item</th>
                <th className="px-4 py-2 font-semibold">Product</th>
                <th className="px-4 py-2 font-semibold">Fabric / Style / Fit</th>
                <th className="px-4 py-2 font-semibold">Status</th>
                <th className="px-4 py-2 font-semibold">Rack</th>
                <th className="px-4 py-2 font-semibold">Delivery</th>
                <th className="px-4 py-2 font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--color-border)]">
              {items.map((item) => {
                const fsf = [item.fabric_summary, item.style_summary, item.fit_summary].filter(Boolean).join(' / ')
                return (
                  <tr key={item.id} className="text-[var(--color-text-primary)]">
                    <td className="px-4 py-3 ss-mono text-xs font-semibold">{item.item_code}</td>
                    <td className="px-4 py-3 capitalize">{item.product_type ?? 'Garment'} × {item.quantity ?? 1}</td>
                    <td className="px-4 py-3 text-[var(--color-text-secondary)]">{fsf || '—'}</td>
                    <td className="px-4 py-3">
                      <ProductionBadge state={item.state ?? 'draft'} label={item.production_state_label ?? undefined} />
                    </td>
                    <td className="px-4 py-3 text-[var(--color-text-secondary)]">{item.ready_rack_slot ?? '—'}</td>
                    <td className="px-4 py-3 text-[var(--color-text-secondary)]">
                      {item.is_delivered ? 'Delivered' : 'Not Delivered'}
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        {canPrintJobCard && (
                          <button
                            onClick={() => printItemJobCard(item.id)}
                            disabled={jobCardBusy === item.id}
                            title="Generate & print this shirt's job card"
                            className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium border border-[var(--color-border-mid)] text-[var(--color-text-secondary)] rounded hover:bg-[var(--color-surface-alt)] disabled:opacity-50 transition-colors"
                          >
                            <Printer size={12} strokeWidth={1.75} /> {jobCardBusy === item.id ? '…' : 'Job Card'}
                          </button>
                        )}
                        {item.measurement_version_id ? (
                          <button
                            onClick={() => setMeasureItem({ versionId: item.measurement_version_id as number, item_code: item.item_code })}
                            title="View this shirt's measurements"
                            className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium border border-[var(--color-border-mid)] text-[var(--color-text-secondary)] rounded hover:bg-[var(--color-surface-alt)] transition-colors"
                          >
                            <Ruler size={12} strokeWidth={1.75} /> Measure
                          </button>
                        ) : null}
                        {item.state === 'delivered' || item.is_delivered ? (
                          <span className="text-xs text-[var(--color-text-muted)]">Delivered</span>
                        ) : item.state === 'ready_for_delivery' ? (
                          canPickup ? (
                            <button
                              onClick={() => setPickupItem({ id: item.id, item_code: item.item_code })}
                              className="px-2 py-1 text-xs font-medium border border-[var(--color-brand)] text-[var(--color-brand)] rounded hover:bg-[var(--color-brand-light)] transition-colors"
                            >
                              Collect &amp; Handover
                            </button>
                          ) : (
                            <span className="text-xs text-[var(--color-text-muted)]">Ready</span>
                          )
                        ) : (
                          <span className="text-xs text-[var(--color-text-muted)]">Not ready</span>
                        )}
                      </div>
                    </td>
                  </tr>
                )
              })}
              {items.length === 0 && (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-sm text-[var(--color-text-muted)]">No items</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Payments — balance collection + history (Phase 3B-1) */}
      <div id="order-payments">
        <OrderPaymentsSection orderId={orderId} />
      </div>

      {/* Past pickups (Phase 2 batches) + receipt reprint */}
      <OrderPickupHistory orderId={orderId} />

      {/* All generated PDFs for the order (invoices, job cards, receipts) */}
      <OrderDocuments orderId={orderId} />

      {/* Collapsible order history — lifecycle, payments, production, pickups (#4) */}
      <OrderTimeline orderId={orderId} />

      {/* Pickup / handover with balance gate (Phase 3B-3) */}
      <PickupHandoverPanel orderId={orderId} />

      {/* Customer WhatsApp notifications + history (Phase 4) */}
      <OrderNotificationsSection orderId={orderId} events={waEvents} />

      {/* Customer alteration after delivery — shown once a sub-order is delivered (Phase 5) */}
      <OrderAlterationsSection
        orderId={orderId}
        items={items.map((i) => ({ id: i.id, item_code: i.item_code, product_type: i.product_type, state: i.state }))}
      />

      <ConfirmDialog
        open={cancelOpen}
        onClose={() => setCancelOpen(false)}
        onConfirm={async (reason) => handleCancel(reason ?? '')}
        title="Cancel Order"
        description="This will cancel the entire order. Items in production cannot be recalled."
        variant="destructive"
        requireReason
        loading={cancelMutation.isPending}
      />

      {editOpen && (
        <EditOrderModal
          orderId={orderId}
          current={{ expected_delivery_date: order.expected_delivery_date, delivery_mode: order.delivery_mode, notes: order.notes }}
          open={editOpen}
          onClose={() => setEditOpen(false)}
        />
      )}

      {measureItem && (
        <MeasurementViewModal
          versionId={measureItem.versionId}
          itemCode={measureItem.item_code}
          open={!!measureItem}
          onClose={() => setMeasureItem(null)}
        />
      )}

      {pickupItem && (
        <PickupItemModal
          orderId={orderId}
          item={pickupItem}
          open={pickupItem !== null}
          onClose={() => setPickupItem(null)}
        />
      )}
    </div>
  )
}
