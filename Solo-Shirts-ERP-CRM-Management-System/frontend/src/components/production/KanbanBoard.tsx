'use client'

import { useEffect, useState } from 'react'
import { toast } from 'sonner'
import { Layers } from 'lucide-react'
import { useProductionBoard, useMySections, useTransitionItem, type BoardFilters } from '@/lib/api/hooks/useProduction'
import { ProductionCard } from './ProductionCard'
import { BoardFilterBar } from './BoardFilterBar'
import { StageMoveDialog, type StageMovePayload } from './StageMoveDialog'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { getStageLabel } from '@/lib/config/stage-labels'
import { priorityWeight } from '@/lib/config/priority'
import { permittedTransitions } from '@/lib/config/transitions'
import { usePermission, ROLES } from '@/lib/auth/permissions'
import { cn } from '@/lib/utils'
import type { ProductionItem } from '@/lib/api/schemas/production'

// Stages grouped into phase bands. Every stage is always rendered (empty ones
// collapse to a thin spine) so the pipeline stays legible. Draft/Delivered/
// Cancelled are intentionally absent — cancelled lives in search/history only.
const PHASE_BANDS: Array<{ label: string; cols: string[] }> = [
  { label: 'Fabric', cols: ['FabricAllocated'] },
  { label: 'Cut & Sew', cols: ['Cutting', 'Tailoring', 'KajaButton', 'Finishing'] },
  { label: 'QC', cols: ['QC', 'Rework'] },
  { label: 'Dispatch', cols: ['Packing', 'ReadyForDelivery'] },
]

// Within a column: overdue first, then by priority (urgent → high → normal).
// Stable for equal weights, so the backend's item_code order is preserved.
function boardSort(a: ProductionItem, b: ProductionItem): number {
  const overdue = Number(b.is_overdue ?? false) - Number(a.is_overdue ?? false)
  if (overdue !== 0) return overdue
  return priorityWeight(b.priority) - priorityWeight(a.priority)
}

function toggleCls(active: boolean): string {
  return cn(
    'px-2.5 py-1 rounded-md font-medium transition-colors',
    active ? 'bg-[var(--color-brand)] text-white' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]',
  )
}

// Drag-and-drop is a desktop-only supervisor override; it must never appear on a
// touch device (where it would fight scrolling) — gate on a fine pointer.
function useDesktopPointer(): boolean {
  const [fine, setFine] = useState(false)
  useEffect(() => {
    if (typeof window === 'undefined' || !window.matchMedia) return
    const mq = window.matchMedia('(pointer: fine)')
    setFine(mq.matches)
    const onChange = (e: MediaQueryListEvent) => setFine(e.matches)
    mq.addEventListener('change', onChange)
    return () => mq.removeEventListener('change', onChange)
  }, [])
  return fine
}

interface DragState {
  item: ProductionItem
  /** PascalCase stages this user may legally move the item to. */
  legal: Set<string>
}

export function KanbanBoard() {
  const { data: mySections } = useMySections()
  const { can, is } = usePermission()
  const isDesktop = useDesktopPointer()
  const hasSections = (mySections?.length ?? 0) > 0
  const [mineOnly, setMineOnly] = useState(false)
  const [filters, setFilters] = useState<BoardFilters>({})

  // Drag is a manager/supervisor override on the desktop board only. Floor staff
  // and tablets keep the button-driven Advance flow.
  const canDrag = isDesktop && is([ROLES.OWNER, ROLES.ADMIN, ROLES.PRODUCTION])

  const [drag, setDrag] = useState<DragState | null>(null)
  const [overCol, setOverCol] = useState<string | null>(null)
  const [pendingDrop, setPendingDrop] = useState<{ item: ProductionItem; toState: string } | null>(null)
  const [highlightOrder, setHighlightOrder] = useState<number | null>(null)

  const { data: board, isLoading } = useProductionBoard({ polling: true, mine: mineOnly && hasSections, filters })
  const columns = board?.columns ?? {}
  const totalItems = PHASE_BANDS.reduce((sum, b) => sum + b.cols.reduce((s, c) => s + (columns[c]?.length ?? 0), 0), 0)

  function startDrag(item: ProductionItem) {
    setDrag({ item, legal: new Set(permittedTransitions(item.allowed_transitions, can)) })
  }
  function endDrag() {
    setDrag(null)
    setOverCol(null)
  }
  function dropOn(colKey: string) {
    if (!drag || !drag.legal.has(colKey)) return
    setPendingDrop({ item: drag.item, toState: colKey })
    endDrag()
  }
  function toggleHighlight(orderId: number) {
    setHighlightOrder((prev) => (prev === orderId ? null : orderId))
  }

  function renderColumn(colKey: string) {
    const items: ProductionItem[] = [...(columns[colKey] ?? [])].sort(boardSort)
    const isLegal = drag !== null && drag.legal.has(colKey)
    const dragActive = drag !== null
    // Collapse to a spine only when empty AND not a live drop target.
    const collapsed = items.length === 0 && !(dragActive && isLegal)

    if (collapsed) {
      return (
        <div
          key={colKey}
          title={`${getStageLabel(colKey)} — 0`}
          className={cn(
            'flex w-9 shrink-0 flex-col items-center gap-2 rounded-xl border border-dashed border-[var(--color-border-mid)] bg-[var(--color-surface-alt)]/40 py-3',
            dragActive && !isLegal && 'opacity-40',
          )}
        >
          <span className="text-[10px] font-bold text-[var(--color-text-muted)]">0</span>
          <span className="[writing-mode:vertical-rl] rotate-180 text-[10px] font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">
            {getStageLabel(colKey)}
          </span>
        </div>
      )
    }

    return (
      <div
        key={colKey}
        onDragOver={dragActive && isLegal ? (e) => { e.preventDefault(); setOverCol(colKey) } : undefined}
        onDragLeave={dragActive ? () => setOverCol((c) => (c === colKey ? null : c)) : undefined}
        onDrop={dragActive && isLegal ? () => dropOn(colKey) : undefined}
        className={cn(
          'flex min-w-[200px] max-w-[220px] shrink-0 flex-col gap-3 rounded-xl p-1 transition-colors',
          dragActive && isLegal && 'ring-2 ring-[var(--color-brand)] ring-offset-1 bg-[var(--color-brand-light)]/30',
          dragActive && isLegal && overCol === colKey && 'bg-[var(--color-brand-light)]/70',
          dragActive && !isLegal && 'opacity-40',
        )}
      >
        <div className="flex items-center justify-between px-1">
          <p className="text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">
            {getStageLabel(colKey)}
          </p>
          <span className="flex h-5 w-5 items-center justify-center rounded-full bg-[var(--color-brand-light)] text-xs font-bold text-[var(--color-brand)]">
            {items.length}
          </span>
        </div>
        <div className="flex flex-col gap-2">
          {items.map((item) => (
            <ProductionCard
              key={item.id}
              item={item}
              draggable={canDrag}
              onDragStartItem={startDrag}
              onDragEndItem={endDrag}
              onToggleHighlight={toggleHighlight}
              highlighted={highlightOrder !== null && item.order_id === highlightOrder}
              dimmed={highlightOrder !== null && item.order_id !== highlightOrder}
            />
          ))}
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <BoardFilterBar filters={filters} onChange={setFilters} />
        {hasSections && (
          <div className="inline-flex rounded-lg border border-[var(--color-border-mid)] p-0.5 text-xs">
            <button onClick={() => setMineOnly(false)} className={toggleCls(!mineOnly)}>
              All sections
            </button>
            <button onClick={() => setMineOnly(true)} className={toggleCls(mineOnly)}>
              My section
            </button>
          </div>
        )}
      </div>

      {highlightOrder !== null && (
        <div className="flex items-center gap-2 rounded-lg border border-[var(--color-brand-muted)] bg-[var(--color-brand-light)]/40 px-3 py-1.5 text-xs">
          <Layers size={13} className="text-[var(--color-brand)]" />
          <span className="font-medium text-[var(--color-text-secondary)]">
            Highlighting one order across all stages
          </span>
          <button onClick={() => setHighlightOrder(null)} className="ml-auto font-semibold text-[var(--color-brand)] hover:underline">
            Clear
          </button>
        </div>
      )}

      {canDrag && (
        <p className="text-[11px] text-[var(--color-text-muted)]">
          Tip: drag a card to a highlighted stage to move it — illegal stages are dimmed, and you confirm before anything changes.
        </p>
      )}

      {isLoading ? (
        <TableSkeleton rows={4} cols={5} />
      ) : totalItems === 0 ? (
        <div className="py-16 text-center text-sm text-[var(--color-text-muted)]">
          {Object.keys(filters).length > 0
            ? 'No items match your filters'
            : mineOnly
              ? 'No items in your section'
              : 'No items in production'}
        </div>
      ) : (
        <div className="flex gap-5 overflow-x-auto pb-4">
          {PHASE_BANDS.map((band) => (
            <div key={band.label} className="flex flex-col gap-2">
              <div className="flex items-center gap-2 px-1">
                <span className="text-[11px] font-bold uppercase tracking-wider text-[var(--color-text-secondary)]">
                  {band.label}
                </span>
                <span className="h-px w-6 bg-[var(--color-border-mid)]" />
              </div>
              <div className="flex gap-3">{band.cols.map(renderColumn)}</div>
            </div>
          ))}
        </div>
      )}

      {pendingDrop && (
        <BoardDropDialog
          item={pendingDrop.item}
          toState={pendingDrop.toState}
          onClose={() => setPendingDrop(null)}
        />
      )}
    </div>
  )
}

/**
 * Confirmation for a drag-drop move. Mounted only while a drop is pending so its
 * transition hook is scoped to the dropped item. Crucially, the board is NOT
 * mutated optimistically: the card stays in its source column until the backend
 * transition succeeds and the board refetches. On failure nothing has moved — the
 * card simply stays put and an error toast is shown (the spec's "snap back").
 */
function BoardDropDialog({ item, toState, onClose }: { item: ProductionItem; toState: string; onClose: () => void }) {
  const transition = useTransitionItem(item.id)

  async function handleConfirm(payload: StageMovePayload) {
    try {
      await transition.mutateAsync({ to: toState, ...payload })
      toast.success(`Moved ${item.item_code ?? item.order_number ?? 'item'} to ${getStageLabel(toState)}`)
      onClose()
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Move failed — card stays put')
    }
  }

  return (
    <StageMoveDialog
      open
      onClose={onClose}
      onConfirm={handleConfirm}
      toState={toState}
      toLabel={getStageLabel(toState)}
      fromLabel={getStageLabel(item.production_state)}
      quantity={item.quantity ?? 1}
      customerName={item.customer_name}
      currentBox={item.delivery_box_code}
      loading={transition.isPending}
    />
  )
}
