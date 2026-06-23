'use client'

import Link from 'next/link'
import { format } from 'date-fns'
import { Box, PackageCheck, Wallet, Zap, ArrowRight, MessageCircle, Scissors } from 'lucide-react'
import { cn, formatINR } from '@/lib/utils'
import { StatusBadge } from '@/components/ui/status-badge'
import { orderDisplayStatus } from '@/lib/orders/displayStatus'
import { productionStateLabel, type LookupOrder } from '@/lib/api/hooks/useFrontDeskLookup'

export function OrderLookupCard({ order }: { order: LookupOrder }) {
  const lifecycle = orderDisplayStatus(order)
  // Prefer the rich production rollup (Partially Ready / Partially Delivered)
  // once the order is in production; fall back to the lifecycle label for
  // intake / cancelled states.
  const useProgress = order.progress && !['intake_preparation', 'cancelled'].includes(lifecycle.value)
  const status = useProgress
    ? { value: order.progress!.aggregate_status, label: order.progress!.aggregate_status_label }
    : lifecycle
  const balance = order.invoice?.balance_amount ?? 0
  const hasBalance = order.invoice !== null && balance > 0
  const hasReadyRack = order.items.some((i) => i.ready_rack_slot)
  const hasDelivered = order.items.some((i) => i.status === 'delivered')

  return (
    <div className="rounded-2xl border border-[var(--color-border)] bg-white p-4">
      {/* Header */}
      <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
        <span className="ss-mono text-sm font-semibold text-[var(--color-text-primary)]">{order.order_code}</span>
        <span className="text-sm text-[var(--color-text-secondary)]">{order.customer_name ?? '—'}</span>
        {order.phone_masked && <span className="text-xs text-[var(--color-text-muted)]">{order.phone_masked}</span>}
        {order.is_rush && (
          <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-[var(--color-warning)]">
            <Zap size={11} strokeWidth={1.75} /> Rush
          </span>
        )}
        <StatusBadge status={status.value} label={status.label} className="ml-auto" />
      </div>

      {order.progress && (
        <div className="mt-1 text-xs text-[var(--color-text-secondary)]">{order.progress.summary_label}</div>
      )}

      <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-[var(--color-text-muted)]">
        <span>Delivery: {order.delivery_date ? format(new Date(order.delivery_date), 'dd MMM yyyy') : '—'}</span>
        {order.invoice && (
          <span className={cn('inline-flex items-center gap-1 font-medium', hasBalance ? 'text-[var(--color-warning)]' : 'text-[var(--color-success)]')}>
            <Wallet size={12} strokeWidth={1.75} />
            {hasBalance ? `Balance ${formatINR(balance)} pending` : 'Fully paid'}
          </span>
        )}
      </div>

      {/* Sub-orders */}
      <div className="mt-3 space-y-1.5">
        {order.items.map((item) => (
          <div key={item.id} className="flex flex-wrap items-center gap-x-2.5 gap-y-1 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm">
            <span className="ss-mono text-[var(--color-text-primary)]">{item.item_code}</span>
            <span className="text-[var(--color-text-muted)]">→</span>
            <span className="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700">
              {productionStateLabel(item.status)}
            </span>
            {/* Production box (during production) */}
            {item.box_code && (
              <span className="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">
                <Box size={11} strokeWidth={1.75} /> Box {item.box_code}
              </span>
            )}
            {/* Ready rack (pickup location) — only when staged */}
            {item.ready_rack_slot && (
              <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700">
                <PackageCheck size={11} strokeWidth={1.75} /> Rack {item.ready_rack_slot}
              </span>
            )}
            {/* Delivery box — the manually-entered pickup box / shelf number */}
            {item.delivery_box_code && (
              <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700">
                <PackageCheck size={11} strokeWidth={1.75} /> Box {item.delivery_box_code}
              </span>
            )}
          </div>
        ))}
      </div>

      {/* Actions */}
      <div className="mt-3 flex flex-wrap gap-2 border-t border-[var(--color-border)] pt-3">
        <Link
          href={`/orders/${order.id}`}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-9 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
        >
          Open Order <ArrowRight size={13} strokeWidth={2} />
        </Link>
        {hasBalance && (
          <Link
            href={`/orders/${order.id}`}
            className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-3 h-9 text-xs font-medium text-white hover:bg-[var(--color-brand-dark)] transition-colors"
          >
            <Wallet size={13} strokeWidth={1.75} /> Collect Payment
          </Link>
        )}
        <Link
          href={`/front-desk/ready-rack?q=${encodeURIComponent(order.order_code)}`}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-9 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
        >
          <PackageCheck size={13} strokeWidth={1.75} /> Search Ready Rack
        </Link>
        {hasReadyRack && (
          <Link
            href={`/orders/${order.id}`}
            className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-brand)] px-3 h-9 text-xs font-medium text-[var(--color-brand-dark)] hover:bg-[var(--color-brand-light)] transition-colors"
          >
            <PackageCheck size={13} strokeWidth={1.75} /> Start Handover
          </Link>
        )}
        <Link
          href={`/orders/${order.id}`}
          className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-9 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
        >
          <MessageCircle size={13} strokeWidth={1.75} /> Send WhatsApp
        </Link>
        {hasDelivered && (
          <Link
            href={`/orders/${order.id}`}
            className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-brand)] px-3 h-9 text-xs font-medium text-[var(--color-brand-dark)] hover:bg-[var(--color-brand-light)] transition-colors"
          >
            <Scissors size={13} strokeWidth={1.75} /> Request Alteration
          </Link>
        )}
      </div>
    </div>
  )
}
