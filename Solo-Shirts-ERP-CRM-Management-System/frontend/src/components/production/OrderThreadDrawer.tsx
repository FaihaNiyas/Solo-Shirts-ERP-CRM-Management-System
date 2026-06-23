'use client'

import Link from 'next/link'
import { CalendarClock, PauseCircle, CheckCircle2, PackageCheck, XCircle, ArrowUpRight } from 'lucide-react'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { useOrderProductionSummary, type OrderSummaryItem } from '@/lib/api/hooks/useProduction'
import { cn } from '@/lib/utils'

interface Props {
  open: boolean
  onClose: () => void
  orderId: number
  /** The item the user opened the thread from — highlighted in the list. */
  focusItemId?: number
}

function shortDate(d?: string | null): string | null {
  if (!d) return null
  const dt = new Date(d)
  if (Number.isNaN(dt.getTime())) return null
  return dt.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

// Colour the stage dot by where the item sits: green = ready/delivered,
// rose = cancelled, slate = on hold, brand = mid-production.
function stageTone(item: OrderSummaryItem): string {
  if (item.is_cancelled) return 'bg-rose-400'
  if (item.is_delivered || item.is_ready) return 'bg-green-500'
  if (item.is_on_hold) return 'bg-slate-400'
  return 'bg-[var(--color-brand)]'
}

/**
 * Read-only "order thread": every sub-order of a Main Order with its current
 * production stage. Opened from a card's order code so a supervisor can see where
 * the rest of a customer's order is — without ever moving the order as one unit.
 * Strictly informational: no transitions happen here.
 */
export function OrderThreadDrawer({ open, onClose, orderId, focusItemId }: Props) {
  const { data, isLoading } = useOrderProductionSummary(orderId, open)

  return (
    <DrawerPanel
      open={open}
      onClose={onClose}
      title={
        <Link
          href={`/orders/${orderId}`}
          onClick={onClose}
          className="group inline-flex items-center gap-1 hover:text-[var(--color-brand)] transition-colors"
          title="Open full order"
        >
          {data?.order_code ?? 'Order'}
          <ArrowUpRight size={15} strokeWidth={2} className="text-[var(--color-text-muted)] group-hover:text-[var(--color-brand)]" />
        </Link>
      }
      description={data?.customer_name ?? undefined}
      width="sm"
    >
      {isLoading || !data ? (
        <div className="space-y-2">
          {[0, 1, 2].map((i) => (
            <div key={i} className="h-12 rounded-lg ss-shimmer" />
          ))}
        </div>
      ) : (
        <div className="space-y-4">
          {/* Rollup */}
          <div className="rounded-xl border border-[var(--color-border)] bg-[var(--color-surface-alt)] p-3">
            <p className="text-sm font-semibold text-[var(--color-text-primary)]">
              {data.progress.aggregate_status_label}
            </p>
            <p className="mt-0.5 text-xs text-[var(--color-text-muted)]">{data.progress.summary_label}</p>
            {data.expected_delivery_date && (
              <p className="mt-2 inline-flex items-center gap-1 text-xs text-[var(--color-text-secondary)]">
                <CalendarClock size={12} /> Due {shortDate(data.expected_delivery_date)}
              </p>
            )}
          </div>

          {/* Sibling items */}
          <div className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">
              {data.items.length} item{data.items.length === 1 ? '' : 's'} in this order
            </p>
            {data.items.map((item) => {
              const focused = item.id === focusItemId
              return (
                <div
                  key={item.id}
                  className={cn(
                    'flex items-center justify-between gap-3 rounded-lg border p-2.5',
                    focused
                      ? 'border-[var(--color-brand)] bg-[var(--color-brand-light)]/40'
                      : 'border-[var(--color-border)] bg-white',
                  )}
                >
                  <div className="flex min-w-0 items-center gap-2.5">
                    <span className={cn('h-2.5 w-2.5 shrink-0 rounded-full', stageTone(item))} />
                    <div className="min-w-0">
                      <p className="truncate font-mono text-xs font-semibold text-[var(--color-text-primary)]">
                        {item.item_code ?? `#${item.id}`}
                      </p>
                      <p className="truncate text-[11px] capitalize text-[var(--color-text-muted)]">
                        {item.product_type ?? 'garment'}
                      </p>
                    </div>
                  </div>
                  <div className="flex shrink-0 items-center gap-1.5">
                    {item.is_on_hold && <PauseCircle size={13} className="text-slate-400" />}
                    {item.is_overdue && <span className="text-[10px] font-medium text-red-600">Overdue</span>}
                    {item.is_delivered ? (
                      <PackageCheck size={13} className="text-green-600" />
                    ) : item.is_ready ? (
                      <CheckCircle2 size={13} className="text-green-600" />
                    ) : item.is_cancelled ? (
                      <XCircle size={13} className="text-rose-400" />
                    ) : null}
                    <span className="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700">
                      {item.state_label ?? item.state}
                    </span>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      )}
    </DrawerPanel>
  )
}
