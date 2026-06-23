'use client'

import Link from 'next/link'
import { format } from 'date-fns'
import { PackageCheck, AlertTriangle, Clock, ArrowRight, MessageCircle } from 'lucide-react'
import { cn, formatINR } from '@/lib/utils'
import { productionStateLabel, type RackResult } from '@/lib/api/hooks/useFrontDeskLookup'

export function RackResultCard({ result }: { result: RackResult }) {
  const hasBalance = result.balance_amount > 0
  const otherItems = result.other_items ?? []

  return (
    <div className="rounded-2xl border border-[var(--color-border)] bg-white p-4">
      <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
        <span className="ss-mono text-sm font-semibold text-[var(--color-text-primary)]">{result.order_code}</span>
        <span className="text-sm text-[var(--color-text-secondary)]">{result.customer_name ?? '—'}</span>
        {result.phone_masked && <span className="text-xs text-[var(--color-text-muted)]">{result.phone_masked}</span>}
        <span
          className={cn(
            'ml-auto rounded-full px-2.5 py-0.5 text-xs font-medium',
            result.progress?.aggregate_status === 'ready'
              ? 'bg-green-100 text-green-700'
              : result.ready
                ? 'bg-amber-100 text-amber-800'
                : 'bg-gray-100 text-gray-500',
          )}
        >
          {result.progress?.aggregate_status_label ?? (result.ready ? 'Ready for Pickup' : 'Not ready')}
        </span>
      </div>

      {/* Derived progress — makes clear how much of the order is collectable. */}
      {result.progress && (
        <div className="mt-1 text-xs text-[var(--color-text-secondary)]">{result.progress.summary_label}</div>
      )}
      <div className="mt-1 text-xs text-[var(--color-text-muted)]">
        Delivery: {result.delivery_date ? format(new Date(result.delivery_date), 'dd MMM yyyy') : '—'}
      </div>

      {result.ready && (
        <div className="mt-3 space-y-1.5">
          <p className="text-[11px] font-semibold uppercase tracking-wide text-green-700">Ready for Pickup</p>
          {result.ready_sub_orders.map((i) => (
            <div key={i.item_code} className="flex items-center gap-2 text-xs">
              <PackageCheck size={12} strokeWidth={1.75} className="text-green-600 shrink-0" />
              <span className="ss-mono font-medium text-[var(--color-text-primary)]">{i.item_code}</span>
              {i.product_type && <span className="capitalize text-[var(--color-text-muted)]">{i.product_type}</span>}
              {i.ready_rack_slot && (
                <span className="ml-auto rounded-full bg-green-100 px-2 py-0.5 font-medium text-green-700">Rack {i.ready_rack_slot}</span>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Non-ready siblings — never imply the whole order is collectable. */}
      {otherItems.length > 0 && (
        <div className="mt-3 space-y-1.5 rounded-lg bg-[var(--color-surface-alt)] px-3 py-2">
          <p className="flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">
            <Clock size={12} strokeWidth={1.75} /> Other Items Still in Production
          </p>
          {otherItems.map((i) => (
            <div key={i.item_code} className="flex items-center gap-2 text-xs">
              <span className="ss-mono font-medium text-[var(--color-text-primary)]">{i.item_code}</span>
              {i.product_type && <span className="capitalize text-[var(--color-text-muted)]">{i.product_type}</span>}
              <span className="ml-auto text-[var(--color-text-secondary)]">{i.status_label ?? productionStateLabel(i.status)}</span>
            </div>
          ))}
        </div>
      )}

      {!result.ready && otherItems.length === 0 && (
        <div className="mt-3 flex items-center gap-2 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2 text-xs text-blue-700">
          <Clock size={13} strokeWidth={1.75} />
          Order is currently in {productionStateLabel(result.current_status)}. Not ready for pickup.
        </div>
      )}

      {/* Balance is shown for context; collection/handover happen elsewhere. */}
      {hasBalance && (
        <div className="mt-2 flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
          <AlertTriangle size={13} strokeWidth={1.75} />
          Balance {formatINR(result.balance_amount)} pending. Collect before handover.
        </div>
      )}

      <div className="mt-3 flex flex-wrap gap-2 border-t border-[var(--color-border)] pt-3">
        <Link
          href={`/orders/${result.order_id}`}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-9 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
        >
          Open Order <ArrowRight size={13} strokeWidth={2} />
        </Link>
        {result.ready && (
          <Link
            href={`/orders/${result.order_id}`}
            className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-3 h-9 text-xs font-medium text-white hover:bg-[var(--color-brand-dark)] transition-colors"
          >
            <PackageCheck size={13} strokeWidth={1.75} /> Start Handover
          </Link>
        )}
        <Link
          href={`/orders/${result.order_id}`}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-9 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
        >
          <MessageCircle size={13} strokeWidth={1.75} /> Send WhatsApp
        </Link>
      </div>
    </div>
  )
}
