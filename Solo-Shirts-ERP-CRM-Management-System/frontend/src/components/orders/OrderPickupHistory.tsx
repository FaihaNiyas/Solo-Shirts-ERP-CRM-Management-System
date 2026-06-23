'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { Package, Printer } from 'lucide-react'
import { formatINR } from '@/lib/utils'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { usePickupBatches } from '@/lib/api/hooks/usePickup'

const STATUS_STYLE: Record<string, string> = {
  handed_over: 'bg-green-100 text-green-700',
  paid: 'bg-blue-50 text-blue-700',
  payment_pending: 'bg-amber-100 text-amber-800',
  cancelled: 'bg-gray-100 text-gray-500',
}

/** Past pickups for the order (Phase 2 batches), newest first, with receipt reprint. */
export function OrderPickupHistory({ orderId }: { orderId: number }) {
  const { data: batches, isLoading } = usePickupBatches(orderId)
  const [busy, setBusy] = useState<number | null>(null)

  async function reprint(batchId: number) {
    setBusy(batchId)
    try {
      const res = await apiGet<{ download_url?: string; url?: string }>(ENDPOINTS.pickupBatchReceipt(orderId, batchId))
      const url = res.data.download_url ?? res.data.url
      if (url) window.open(url, '_blank', 'noopener')
      else toast.error('Receipt URL unavailable.')
    } catch (err) {
      toast.error((err as { message?: string })?.message ?? 'Could not open the receipt.')
    } finally {
      setBusy(null)
    }
  }

  if (isLoading) return null
  if (!batches || batches.length === 0) return null

  return (
    <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3">
      <h2 className="text-base font-semibold text-[var(--color-text-primary)]">Pickups</h2>
      <div className="space-y-2">
        {batches.map((b) => (
          <div key={b.id} className="rounded-lg border border-[var(--color-border)] px-3 py-2.5">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
              <Package size={14} strokeWidth={1.75} className="text-[var(--color-text-muted)]" />
              <span className="ss-mono text-sm font-semibold text-[var(--color-text-primary)]">{b.batch_no}</span>
              <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${STATUS_STYLE[b.status] ?? 'bg-gray-100 text-gray-600'}`}>
                {b.status.replace('_', ' ')}
              </span>
              <span className="text-xs text-[var(--color-text-muted)]">
                {b.items.length} item{b.items.length === 1 ? '' : 's'} · paid {formatINR(b.paid_amount)}
              </span>
              {(b.status === 'handed_over' || b.status === 'paid') && (
                <button
                  onClick={() => reprint(b.id)}
                  disabled={busy === b.id}
                  className="ml-auto inline-flex items-center gap-1 px-2 py-1 text-xs font-medium border border-[var(--color-border-mid)] text-[var(--color-text-secondary)] rounded hover:bg-[var(--color-surface-alt)] disabled:opacity-50"
                >
                  <Printer size={12} strokeWidth={1.75} /> {busy === b.id ? '…' : 'Receipt'}
                </button>
              )}
            </div>
            <div className="mt-1.5 flex flex-wrap gap-1.5">
              {b.items.map((it) => (
                <span key={it.order_item_id} className="ss-mono rounded bg-[var(--color-surface-alt)] px-1.5 py-0.5 text-[11px] text-[var(--color-text-secondary)]">
                  {it.item_code}
                </span>
              ))}
            </div>
          </div>
        ))}
      </div>
    </section>
  )
}
