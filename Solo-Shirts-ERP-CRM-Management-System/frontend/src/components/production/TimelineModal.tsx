'use client'

import { ModalDialog } from '@/components/ui/modal-dialog'
import { CardSkeleton } from '@/components/ui/loading-skeleton'
import { useProductionHistory } from '@/lib/api/hooks/useProduction'
import { productionStateLabel } from '@/lib/orders/productionState'

interface Props {
  open: boolean
  onClose: () => void
  itemId: number
  title?: string | null
}

interface TimelineEntry {
  id: number
  from_state: string | null
  to_state: string
  actor_name?: string | null
  notes?: string | null
  completed_qty?: number | null
  rejected_qty?: number | null
  attachment_path?: string | null
  occurred_at: string
}

function fmt(iso: string): string {
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleString(undefined, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

export function TimelineModal({ open, onClose, itemId, title }: Props) {
  // Only fetch while the modal is open (the hook is disabled for id <= 0).
  const { data, isLoading } = useProductionHistory(open ? itemId : 0)
  const entries = (data ?? []) as unknown as TimelineEntry[]

  return (
    <ModalDialog open={open} onClose={onClose} title="Production timeline" description={title ?? undefined} size="lg">
      {isLoading ? (
        <CardSkeleton />
      ) : entries.length === 0 ? (
        <p className="py-8 text-center text-sm text-[var(--color-text-muted)]">No movements recorded yet.</p>
      ) : (
        <ol className="relative ml-2 border-l border-[var(--color-border-mid)]">
          {entries.map((e) => {
            const hasQty = e.completed_qty != null || e.rejected_qty != null
            return (
              <li key={e.id} className="ml-4 pb-4 last:pb-0">
                <span className="absolute -left-[5px] mt-1 h-2.5 w-2.5 rounded-full bg-[var(--color-brand)]" />
                <p className="text-sm font-medium text-[var(--color-text-primary)]">
                  {e.from_state ? `${productionStateLabel(e.from_state)} → ` : ''}
                  {productionStateLabel(e.to_state)}
                </p>
                <p className="text-xs text-[var(--color-text-muted)]">
                  {fmt(e.occurred_at)}
                  {e.actor_name ? ` · ${e.actor_name}` : ''}
                </p>
                {hasQty && (
                  <p className="mt-0.5 text-xs text-[var(--color-text-secondary)]">
                    ✓ {e.completed_qty ?? 0} completed · ✗ {e.rejected_qty ?? 0} rejected
                  </p>
                )}
                {e.notes && (
                  <p className="mt-0.5 text-xs italic text-[var(--color-text-secondary)]">“{e.notes}”</p>
                )}
                {e.attachment_path && (
                  <a
                    href={e.attachment_path}
                    target="_blank"
                    rel="noreferrer"
                    className="mt-0.5 inline-block text-xs text-[var(--color-brand)] underline"
                  >
                    Attachment
                  </a>
                )}
              </li>
            )
          })}
        </ol>
      )}
    </ModalDialog>
  )
}
