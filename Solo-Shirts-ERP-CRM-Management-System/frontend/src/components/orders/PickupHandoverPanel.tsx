'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { PackageCheck, AlertTriangle, RotateCw, CheckCircle2, Info } from 'lucide-react'
import { cn, formatINR } from '@/lib/utils'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { productionStateLabel } from '@/lib/api/hooks/useFrontDeskLookup'
import {
  useHandover,
  useHandoverEligibility,
  type HandoverResult,
} from '@/lib/api/hooks/useHandover'

const BLOCKER_LABELS: Record<string, string> = {
  ORDER_NOT_CONFIRMED: 'Order not confirmed yet.',
  ORDER_CANCELLED: 'Order is cancelled.',
  ORDER_NOT_READY: 'Order is not ready for pickup yet.',
  NO_READY_RACK_SLOT: 'Ready items are not on a rack slot.',
  BALANCE_PENDING: 'Balance pending — collect payment first.',
}

export function PickupHandoverPanel({ orderId }: { orderId: number }) {
  const { data: e, isLoading, refetch, isFetching } = useHandoverEligibility(orderId)
  const handover = useHandover(orderId)
  const [dialogOpen, setDialogOpen] = useState(false)
  const [result, setResult] = useState<HandoverResult | null>(null)

  if (isLoading || !e) {
    return (
      <Shell>
        <p className="py-4 text-center text-sm text-[var(--color-text-muted)]">Loading…</p>
      </Shell>
    )
  }

  const allDelivered = e.sub_orders.length > 0 && e.sub_orders.every((s) => s.status === 'delivered')
  const hasBalance = e.balance_amount > 0

  if (result) {
    return (
      <Shell>
        <div className="rounded-xl border border-green-200 bg-green-50 px-4 py-4 text-sm text-green-800">
          <div className="flex items-center gap-2 font-medium">
            <CheckCircle2 size={16} strokeWidth={1.75} /> Order handed over.
          </div>
          <p className="mt-1">
            Delivered: {result.delivered_sub_orders.join(', ') || '—'}.
            {result.released_rack_slots.length > 0 && ` Rack slot ${result.released_rack_slots.join(', ')} released.`}
          </p>
        </div>
      </Shell>
    )
  }

  if (allDelivered) {
    return (
      <Shell>
        <Notice tone="success">Order already delivered.</Notice>
      </Shell>
    )
  }

  async function confirmHandover() {
    try {
      const res = await handover.mutateAsync({ mode: 'pickup' })
      setResult(res)
      setDialogOpen(false)
      toast.success('Order handed over. Rack slot released.')
    } catch (err: unknown) {
      const x = err as { message?: string; request_id?: string }
      toast.error(x?.message ?? 'Handover failed.', { description: x?.request_id ? `request_id: ${x.request_id}` : undefined })
    }
  }

  return (
    <Shell>
      {/* Derived production progress for the whole order. */}
      {e.progress && (
        <p className="text-xs text-[var(--color-text-secondary)]">{e.progress.summary_label}</p>
      )}

      {/* Ready rack slots (pickup location) */}
      {e.rack_slots.length > 0 ? (
        <div className="flex flex-wrap gap-2">
          {e.rack_slots.map((slot) => (
            <span key={slot} className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
              <PackageCheck size={12} strokeWidth={1.75} /> Ready Rack {slot}
            </span>
          ))}
        </div>
      ) : (
        <Notice>This order has no items staged on a ready rack.</Notice>
      )}

      {/* Sub-orders */}
      <div className="space-y-1.5">
        {e.sub_orders.map((s) => (
          <div key={s.item_code} className="flex flex-wrap items-center gap-x-2.5 gap-y-1 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm">
            <span className="ss-mono text-[var(--color-text-primary)]">{s.item_code}</span>
            <span
              className={cn(
                'rounded-full px-2 py-0.5 text-[11px] font-medium',
                s.ready ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500',
              )}
            >
              {s.ready ? 'Ready' : productionStateLabel(s.status)}
            </span>
            {s.rack_slot && <span className="text-[11px] text-[var(--color-text-muted)]">Rack {s.rack_slot}</span>}
          </div>
        ))}
      </div>

      {/* Balance gate */}
      {hasBalance && (
        <div className="flex flex-wrap items-center gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5 text-xs text-amber-800">
          <AlertTriangle size={14} strokeWidth={1.75} />
          <span>Balance {formatINR(e.balance_amount)} pending. Collect payment (Payments above) before handover.</span>
          <button
            onClick={() => refetch()}
            disabled={isFetching}
            className="ml-auto inline-flex items-center gap-1 rounded-lg border border-amber-300 px-2 h-7 font-medium hover:bg-amber-100 disabled:opacity-50"
          >
            <RotateCw size={12} strokeWidth={1.75} /> Re-check
          </button>
        </div>
      )}

      {/* Other blockers */}
      {e.blockers.filter((b) => b !== 'BALANCE_PENDING').length > 0 && (
        <ul className="list-disc pl-6 text-xs text-[var(--color-text-muted)] space-y-0.5">
          {e.blockers.filter((b) => b !== 'BALANCE_PENDING').map((b) => (
            <li key={b}>{BLOCKER_LABELS[b] ?? b}</li>
          ))}
        </ul>
      )}

      {/* Override warning (manager) */}
      {e.warnings.includes('BALANCE_PENDING') && (
        <Notice>Balance pending — handover allowed under your override permission.</Notice>
      )}

      <div>
        <button
          type="button"
          disabled={!e.can_handover || handover.isPending}
          onClick={() => setDialogOpen(true)}
          className="inline-flex items-center gap-1.5 rounded-xl bg-[var(--color-brand)] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
          <PackageCheck size={15} strokeWidth={1.75} /> Hand Over
        </button>
      </div>

      <ConfirmDialog
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
        onConfirm={confirmHandover}
        title="Confirm handover?"
        description={`Confirm handover for ${e.order_code}? This marks the ready sub-orders as delivered${e.rack_slots.length ? ` and releases rack slot ${e.rack_slots.join(', ')}` : ''}.`}
        variant="info"
        confirmLabel="Hand Over"
        loading={handover.isPending}
      />
    </Shell>
  )
}

function Shell({ children }: { children: React.ReactNode }) {
  return (
    <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-4">
      <h2 className="text-base font-semibold text-[var(--color-text-primary)]">Pickup / Handover</h2>
      {children}
    </section>
  )
}

function Notice({ children, tone = 'info' }: { children: React.ReactNode; tone?: 'info' | 'success' }) {
  return (
    <div
      className={cn(
        'flex items-start gap-2 rounded-lg px-3 py-2.5 text-xs',
        tone === 'success' ? 'bg-green-50 border border-green-100 text-green-700' : 'bg-blue-50 border border-blue-100 text-blue-700',
      )}
    >
      <Info size={14} strokeWidth={1.75} className="mt-0.5 shrink-0" />
      <span>{children}</span>
    </div>
  )
}
