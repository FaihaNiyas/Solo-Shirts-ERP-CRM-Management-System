'use client'

import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { format } from 'date-fns'
import { ChevronDown, ChevronRight, Package, Receipt, Wallet, Factory, Truck } from 'lucide-react'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'

interface TimelineEvent {
  id: number
  type: string
  event: string
  created_at: string | null
}

const TYPE_ICON: Record<string, typeof Package> = {
  order: Package,
  invoice: Receipt,
  payment: Wallet,
  production: Factory,
  pickup: Truck,
}

const TYPE_TONE: Record<string, string> = {
  order: 'text-[var(--color-brand)]',
  invoice: 'text-blue-600',
  payment: 'text-green-600',
  production: 'text-amber-600',
  pickup: 'text-purple-600',
}

/** Collapsible order history — lifecycle, invoices, payments, production, pickups. */
export function OrderTimeline({ orderId }: { orderId: number }) {
  const [open, setOpen] = useState(false)
  const { data: events, isLoading } = useQuery({
    queryKey: ['orders', orderId, 'timeline'],
    queryFn: () => apiGet<TimelineEvent[]>(ENDPOINTS.orderTimeline(orderId)),
    select: (res) => res.data,
    enabled: open && orderId > 0,
  })

  return (
    <section className="rounded-xl border border-[var(--color-border)] bg-white">
      <button
        onClick={() => setOpen((o) => !o)}
        className="flex w-full items-center justify-between gap-2 px-4 py-3 text-left"
      >
        <h2 className="text-base font-semibold text-[var(--color-text-primary)]">History</h2>
        {open ? (
          <ChevronDown size={18} strokeWidth={1.75} className="text-[var(--color-text-muted)]" />
        ) : (
          <ChevronRight size={18} strokeWidth={1.75} className="text-[var(--color-text-muted)]" />
        )}
      </button>

      {open && (
        <div className="border-t border-[var(--color-border)] px-4 py-3">
          {isLoading ? (
            <p className="py-4 text-center text-sm text-[var(--color-text-muted)]">Loading history…</p>
          ) : !events || events.length === 0 ? (
            <p className="py-4 text-center text-sm text-[var(--color-text-muted)]">No history yet.</p>
          ) : (
            <ol className="space-y-3">
              {events.map((e) => {
                const Icon = TYPE_ICON[e.type] ?? Package
                return (
                  <li key={e.id} className="flex items-start gap-3">
                    <span className={`mt-0.5 shrink-0 ${TYPE_TONE[e.type] ?? 'text-[var(--color-text-muted)]'}`}>
                      <Icon size={15} strokeWidth={1.75} />
                    </span>
                    <div className="min-w-0">
                      <p className="text-sm text-[var(--color-text-primary)]">{e.event}</p>
                      {e.created_at && (
                        <p className="text-xs text-[var(--color-text-muted)]">
                          {format(new Date(e.created_at), 'dd MMM yyyy, HH:mm')}
                        </p>
                      )}
                    </div>
                  </li>
                )
              })}
            </ol>
          )}
        </div>
      )}
    </section>
  )
}
