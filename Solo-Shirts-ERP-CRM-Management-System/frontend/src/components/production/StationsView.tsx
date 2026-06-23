'use client'

import { useState } from 'react'
import {
  ArrowLeft, Layers, Scissors, Shirt, CircleDot, Sparkles,
  ClipboardCheck, RotateCcw, Package, PackageCheck, ChevronDown, type LucideIcon,
} from 'lucide-react'
import { useProductionBoard } from '@/lib/api/hooks/useProduction'
import { ProductionCard } from './ProductionCard'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { getStageLabel } from '@/lib/config/stage-labels'
import { priorityWeight } from '@/lib/config/priority'
import { cn } from '@/lib/utils'
import type { ProductionItem } from '@/lib/api/schemas/production'

// One tile per workshop stage, in workflow order. Keys match the PascalCase
// column keys the board returns (see useProduction → selectBoard).
interface Station {
  key: string
  icon: LucideIcon
  accent: string
}

const STATIONS: Station[] = [
  { key: 'FabricAllocated', icon: Layers, accent: 'bg-sky-50 text-sky-600' },
  { key: 'Cutting', icon: Scissors, accent: 'bg-amber-50 text-amber-600' },
  { key: 'Tailoring', icon: Shirt, accent: 'bg-violet-50 text-violet-600' },
  { key: 'KajaButton', icon: CircleDot, accent: 'bg-fuchsia-50 text-fuchsia-600' },
  { key: 'Finishing', icon: Sparkles, accent: 'bg-teal-50 text-teal-600' },
  { key: 'QC', icon: ClipboardCheck, accent: 'bg-blue-50 text-blue-600' },
  { key: 'Rework', icon: RotateCcw, accent: 'bg-rose-50 text-rose-600' },
  { key: 'Packing', icon: Package, accent: 'bg-orange-50 text-orange-600' },
  { key: 'ReadyForDelivery', icon: PackageCheck, accent: 'bg-green-50 text-green-600' },
]

// Within a stage: overdue first, then by priority (urgent → high → normal);
// stable for equal weights, so the backend's item_code order is preserved.
function boardSort(a: ProductionItem, b: ProductionItem): number {
  const overdue = Number(b.is_overdue ?? false) - Number(a.is_overdue ?? false)
  if (overdue !== 0) return overdue
  return priorityWeight(b.priority) - priorityWeight(a.priority)
}

/**
 * A simpler, less-wide alternative to the horizontal Kanban: a grid of stage
 * "stations". Pick a station → see only that stage's cards in a responsive grid,
 * move them with the same card actions, then "All stations" to go back. One stage
 * on screen at a time — no horizontal scrolling.
 */
export function StationsView() {
  const [active, setActive] = useState<string | null>(null)
  const { data: board, isLoading } = useProductionBoard({ polling: true })
  const columns = board?.columns ?? {}

  if (isLoading) return <TableSkeleton rows={3} cols={4} />

  // --- Drill-down: one station's cards in a grid -----------------------------
  if (active) {
    const station = STATIONS.find((s) => s.key === active)
    const Icon = station?.icon ?? Layers
    const items = [...(columns[active] ?? [])].sort(boardSort)

    return (
      <div className="space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <button
            onClick={() => setActive(null)}
            className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-10 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]"
          >
            <ArrowLeft size={15} strokeWidth={1.75} /> All stations
          </button>
          <div className="flex items-center gap-2">
            <span className={cn('inline-flex h-8 w-8 items-center justify-center rounded-lg', station?.accent)}>
              <Icon size={16} strokeWidth={1.75} />
            </span>
            <h2 className="text-base font-semibold text-[var(--color-text-primary)]">{getStageLabel(active)}</h2>
            <span className="rounded-full bg-[var(--color-brand-light)] px-2 py-0.5 text-xs font-bold text-[var(--color-brand)]">
              {items.length}
            </span>
          </div>
        </div>

        {items.length === 0 ? (
          <div className="py-16 text-center text-sm text-[var(--color-text-muted)]">
            No items in {getStageLabel(active)}
          </div>
        ) : (
          // Grouped by order: each customer order is one block of its sub-order
          // (shirt) cards, so you can find a customer and move just the cards you
          // need — e.g. 2 of a 10-shirt order — leaving the rest in this stage.
          <div className="space-y-3">
            {groupByOrder(items).map((group) => (
              <OrderGroup key={group.key} group={group} />
            ))}
          </div>
        )}
      </div>
    )
  }

  // --- Landing: grid of station buttons --------------------------------------
  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
      {STATIONS.map((s) => {
        const list = columns[s.key] ?? []
        const count = list.length
        const overdue = list.filter((i) => i.is_overdue).length
        const Icon = s.icon
        return (
          <button
            key={s.key}
            onClick={() => setActive(s.key)}
            className="flex flex-col items-start gap-3 rounded-2xl border border-[var(--color-border)] bg-white p-4 text-left hover:border-[var(--color-brand)] hover:shadow-sm transition-all"
          >
            <span className={cn('inline-flex h-10 w-10 items-center justify-center rounded-xl', s.accent)}>
              <Icon size={20} strokeWidth={1.75} />
            </span>
            <div>
              <p className="text-sm font-semibold text-[var(--color-text-primary)]">{getStageLabel(s.key)}</p>
              <p className="text-xs text-[var(--color-text-muted)]">
                {count === 0 ? 'No items' : `${count} item${count > 1 ? 's' : ''}`}
              </p>
            </div>
            <div className="mt-auto flex items-center gap-2">
              <span className="text-3xl font-bold tabular-nums text-[var(--color-text-primary)]">{count}</span>
              {overdue > 0 && (
                <span className="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-600">
                  {overdue} overdue
                </span>
              )}
            </div>
          </button>
        )
      })}
    </div>
  )
}

interface OrderGroupData {
  key: string
  orderNumber: string | null
  customerName: string | null
  items: ProductionItem[]
}

// Bucket a stage's cards by their order, preserving the incoming (sorted) order
// of both the groups and the cards within each.
function groupByOrder(items: ProductionItem[]): OrderGroupData[] {
  const map = new Map<string, OrderGroupData>()
  for (const it of items) {
    const key = it.order_number ?? String(it.order_id ?? it.id)
    let group = map.get(key)
    if (!group) {
      group = { key, orderNumber: it.order_number ?? null, customerName: it.customer_name ?? null, items: [] }
      map.set(key, group)
    }
    group.items.push(it)
  }
  return [...map.values()]
}

// One customer order: a header (order code · customer · count) over its sub-order
// cards. Collapsible so a busy stage stays scannable; expanded by default.
function OrderGroup({ group }: { group: OrderGroupData }) {
  const [open, setOpen] = useState(true)
  const n = group.items.length

  return (
    <div className="rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface-alt)]/40 overflow-hidden">
      <button
        onClick={() => setOpen((o) => !o)}
        className="flex w-full items-center justify-between gap-2 px-4 py-2.5 hover:bg-[var(--color-surface-alt)] transition-colors"
      >
        <div className="flex min-w-0 items-center gap-2">
          <ChevronDown size={15} strokeWidth={2} className={cn('shrink-0 transition-transform text-[var(--color-text-muted)]', !open && '-rotate-90')} />
          <span className="font-mono text-xs font-semibold text-[var(--color-brand)]">{group.orderNumber ?? '—'}</span>
          <span className="truncate text-sm font-medium text-[var(--color-text-primary)]">{group.customerName ?? '—'}</span>
        </div>
        <span className="shrink-0 rounded-full border border-[var(--color-border)] bg-white px-2 py-0.5 text-[11px] font-bold text-[var(--color-text-secondary)]">
          {n} item{n > 1 ? 's' : ''}
        </span>
      </button>

      {open && (
        <div className="grid grid-cols-1 gap-3 px-3 pb-3 sm:grid-cols-2 lg:grid-cols-3">
          {group.items.map((item) => (
            <ProductionCard key={item.id} item={item} />
          ))}
        </div>
      )}
    </div>
  )
}
