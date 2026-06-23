'use client'

import { use, useState } from 'react'
import Link from 'next/link'
import { format } from 'date-fns'
import { ArrowLeft, FileText, Info, Loader2, Zap } from 'lucide-react'
import { toast } from 'sonner'
import { useQueryClient } from '@tanstack/react-query'
import { PageHeader } from '@/components/ui/page-header'
import { InfoGrid } from '@/components/ui/info-grid'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { productionStateLabel } from '@/lib/api/hooks/useFrontDeskLookup'
import { useTransitionItem } from '@/lib/api/hooks/useProduction'
import { useProductionWorkbench, useTransitionHistory } from '@/lib/api/hooks/useProductionQueue'
import { FabricDamagePanels } from '@/components/production/FabricDamagePanels'
import { QcPanel } from '@/components/production/QcPanel'
import { PackingPanel } from '@/components/production/PackingPanel'

export default function ProductionWorkbenchPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const itemId = parseInt(id)
  const qc = useQueryClient()
  const { data: item, isLoading } = useProductionWorkbench(itemId)
  const { data: history } = useTransitionHistory(itemId)
  const transition = useTransitionItem(itemId)
  const [target, setTarget] = useState<string | null>(null)

  if (isLoading) return <TableSkeleton rows={6} cols={2} />
  if (!item) return <p className="text-sm text-[var(--color-text-muted)]">Item not found</p>

  const needsNotes = target === 'rework' || target === 'cancelled'

  async function openJobCard() {
    if (!item?.order_id) return
    try {
      const res = await apiGet<{ download_url?: string }>(ENDPOINTS.itemJobCard(item.order_id, item.id))
      const url = res.data?.download_url
      if (url) window.open(url, '_blank')
      else toast.error('Job card not available yet.')
    } catch {
      toast.error('Could not open the job card.')
    }
  }

  async function confirmTransition(reason?: string) {
    if (!target) return
    try {
      await transition.mutateAsync({ to: target, notes: reason || undefined })
      toast.success(`Moved to ${productionStateLabel(target)}`)
      setTarget(null)
      qc.invalidateQueries({ queryKey: ['production-workbench', itemId] })
      qc.invalidateQueries({ queryKey: ['production-history', itemId] })
      qc.invalidateQueries({ queryKey: ['production-queue'] })
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Transition failed')
    }
  }

  const measurementEntries = Object.entries(item.measurement ?? {})

  return (
    <div className="space-y-6">
      <Link href="/production/queue" className="inline-flex items-center gap-1.5 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">
        <ArrowLeft size={15} strokeWidth={1.75} /> Production queue
      </Link>

      <PageHeader
        title={item.item_code}
        subtitle={item.order_code ?? `Order #${item.order_id}`}
        actions={
          <div className="flex items-center gap-2">
            <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">{productionStateLabel(item.current_stage)}</span>
            {item.is_rush && (
              <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-[var(--color-warning)]">
                <Zap size={12} strokeWidth={1.75} /> Rush
              </span>
            )}
          </div>
        }
      />

      {/* Read-only context notice — production never edits customer/payment data. */}
      <div className="flex items-start gap-2 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface-alt)] px-4 py-2.5 text-xs text-[var(--color-text-muted)]">
        <Info size={14} strokeWidth={1.75} className="mt-0.5 shrink-0" />
        Shop-floor workbench — customer, pricing and measurement are read-only here.
      </div>

      {item.blockers.length > 0 && (
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800">
          Setup incomplete: {item.blockers.map((b) => b.replace(/_/g, ' ')).join(', ')}.
        </div>
      )}

      {/* Stage actions — only transitions this user may perform. */}
      <section className="rounded-xl border border-[var(--color-border)] bg-white p-4">
        <h2 className="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Move stage</h2>
        {item.allowed_next_stages.length === 0 ? (
          <p className="text-xs text-[var(--color-text-muted)]">No stage actions available to you for this item.</p>
        ) : (
          <div className="flex flex-wrap gap-2">
            {item.allowed_next_stages.map((s) => {
              const danger = s === 'cancelled'
              return (
                <button
                  key={s}
                  type="button"
                  onClick={() => setTarget(s)}
                  className={
                    danger
                      ? 'inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-4 h-10 text-sm font-medium text-[var(--color-danger)] hover:bg-red-50'
                      : 'inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-4 h-10 text-sm font-medium text-white hover:bg-[var(--color-brand-dark)]'
                  }
                >
                  {danger ? 'Cancel item' : `Move to ${productionStateLabel(s)}`}
                </button>
              )
            })}
          </div>
        )}
      </section>

      <InfoGrid
        items={[
          { label: 'Order', value: item.order_code ?? '—' },
          { label: 'Customer', value: item.customer_name ?? '—' },
          { label: 'Product', value: item.product_type === 'pant' ? 'Trouser' : 'Shirt' },
          { label: 'Delivery', value: item.delivery_date ? format(new Date(item.delivery_date), 'dd MMM yyyy') : '—' },
          { label: 'Priority', value: item.is_rush ? 'Rush' : 'Regular' },
          { label: 'Production box', value: item.production_box_code ?? '—' },
          { label: 'Fabric', value: item.fabric ?? '—' },
          { label: 'Style', value: item.style ?? '—' },
          { label: 'Fit', value: item.fit ?? '—' },
          { label: 'Measurement', value: `${item.measurement_profile ?? '—'}${item.measurement_version ? ` · v${item.measurement_version}` : ''}` },
        ]}
      />

      <FabricDamagePanels item={item} />

      <QcPanel item={item} />

      <PackingPanel item={item} />

      <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3">
        <div className="flex items-center justify-between">
          <h2 className="text-sm font-semibold text-[var(--color-text-primary)]">Measurement</h2>
          <button onClick={openJobCard} className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-8 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
            <FileText size={13} strokeWidth={1.75} /> Open job card
          </button>
        </div>
        {measurementEntries.length === 0 ? (
          <p className="text-xs text-[var(--color-text-muted)]">No measurement values recorded.</p>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
            {measurementEntries.map(([k, v]) => (
              <div key={k} className="rounded-lg border border-[var(--color-border)] px-3 py-2">
                <p className="text-[11px] uppercase tracking-wide text-[var(--color-text-muted)]">{k.replace(/_/g, ' ')}</p>
                <p className="text-base font-semibold text-[var(--color-text-primary)]">{v}</p>
              </div>
            ))}
          </div>
        )}
      </section>

      {history && history.length > 0 && (
        <section className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <h2 className="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Stage history</h2>
          <ol className="space-y-3">
            {history.map((h) => (
              <li key={h.id} className="flex gap-3">
                <span className="mt-1 h-2 w-2 shrink-0 rounded-full bg-[var(--color-brand)]" aria-hidden />
                <div className="min-w-0">
                  <p className="text-sm text-[var(--color-text-primary)]">
                    {h.from_state ? productionStateLabel(h.from_state) : 'Start'}
                    <span className="text-[var(--color-text-muted)]"> → </span>
                    <span className="font-medium">{productionStateLabel(h.to_state)}</span>
                  </p>
                  <p className="text-xs text-[var(--color-text-muted)]">
                    {h.occurred_at ? format(new Date(h.occurred_at), 'dd MMM yyyy, HH:mm') : '—'}
                  </p>
                  {h.notes && <p className="mt-0.5 text-xs text-[var(--color-text-secondary)]">{h.notes}</p>}
                </div>
              </li>
            ))}
          </ol>
        </section>
      )}

      <ConfirmDialog
        open={target !== null}
        onClose={() => setTarget(null)}
        onConfirm={confirmTransition}
        title={target ? `Move to ${productionStateLabel(target)}?` : 'Move stage'}
        description="This records a production stage change for this item."
        variant={target === 'cancelled' ? 'danger' : 'info'}
        confirmLabel="Confirm"
        requireReason={needsNotes}
        reasonLabel="Notes"
        loading={transition.isPending}
      />
    </div>
  )
}
