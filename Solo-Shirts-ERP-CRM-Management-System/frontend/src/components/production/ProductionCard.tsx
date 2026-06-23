'use client'

import { useState } from 'react'
import { motion } from 'framer-motion'
import { toast } from 'sonner'
import { CalendarClock, Clock, AlertTriangle, PauseCircle, Play, Flag, UserRound, History, PackageCheck, GripVertical, Layers } from 'lucide-react'
import { StageMoveDialog, type StageMovePayload } from './StageMoveDialog'
import { ReportIssueModal } from './ReportIssueModal'
import { TimelineModal } from './TimelineModal'
import { OrderThreadDrawer } from './OrderThreadDrawer'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { getStageLabel } from '@/lib/config/stage-labels'
import { PRIORITY_CHIP, priorityLabel, type Priority } from '@/lib/config/priority'
import { useTransitionItem, useHoldItem, useResumeItem } from '@/lib/api/hooks/useProduction'
import { usePermission } from '@/lib/auth/permissions'
import { permittedTransitions } from '@/lib/config/transitions'
import { cn } from '@/lib/utils'
import type { ProductionItem } from '@/lib/api/schemas/production'

interface Props {
  item: ProductionItem
  onCardClick?: (item: ProductionItem) => void
  /** Supervisor Board, desktop only: makes the card a drag source. */
  draggable?: boolean
  onDragStartItem?: (item: ProductionItem) => void
  onDragEndItem?: () => void
  /** Sibling-highlight: this card belongs to the highlighted order. */
  highlighted?: boolean
  /** Sibling-highlight: a different order is highlighted, so dim this card. */
  dimmed?: boolean
  /** Board only: toggle the "highlight all of this order" overlay. */
  onToggleHighlight?: (orderId: number) => void
}

// Delivery is an order-desk action — it goes through the order handover flow
// (collect payment, hand over to the customer), NOT the production floor. An
// item's production life ends at "Ready for Delivery", so the board never offers
// a move straight to Delivered even when the backend would technically allow it.
const BOARD_HIDDEN_TRANSITIONS = new Set(['Delivered'])

function timeAgo(iso?: string | null): string | null {
  if (!iso) return null
  const then = new Date(iso).getTime()
  if (Number.isNaN(then)) return null
  const mins = Math.round((Date.now() - then) / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hrs = Math.round(mins / 60)
  if (hrs < 24) return `${hrs}h ago`
  return `${Math.round(hrs / 24)}d ago`
}

function shortDate(d?: string | null): string | null {
  if (!d) return null
  const dt = new Date(d)
  if (Number.isNaN(dt.getTime())) return null
  return dt.toLocaleDateString(undefined, { day: 'numeric', month: 'short' })
}

export function ProductionCard({
  item,
  onCardClick,
  draggable = false,
  onDragStartItem,
  onDragEndItem,
  highlighted = false,
  dimmed = false,
  onToggleHighlight,
}: Props) {
  const { can } = usePermission()
  const [pendingTransition, setPendingTransition] = useState<string | null>(null)
  const [showIssue, setShowIssue] = useState(false)
  const [showHold, setShowHold] = useState(false)
  const [showTimeline, setShowTimeline] = useState(false)
  const [showThread, setShowThread] = useState(false)
  const transition = useTransitionItem(item.id)
  const hold = useHoldItem(item.id)
  const resume = useResumeItem(item.id)

  const canReportIssue = can('production.issue.report')
  const canHold = can('production.hold.manage')

  // Only offer moves this user is allowed to perform, minus delivery (an
  // order-desk action handled in the order flow, not on the production board).
  const transitions = permittedTransitions(item.allowed_transitions, can)
    .filter((t) => !BOARD_HIDDEN_TRANSITIONS.has(t))
  const isOverdue = item.is_overdue
  const isRework = (item.rework_count ?? 0) > 0
  const isOnHold = item.is_on_hold
  const issueCount = item.issue_count ?? 0
  const priority = (item.priority as Priority) ?? 'normal'
  const showPriority = priority !== 'normal'
  const updatedAgo = timeAgo(item.last_transition_at ?? item.updated_at)
  const deadline = shortDate(item.expected_delivery_date)
  const supervisor = item.assigned_supervisor ?? item.assigned_tailor_name ?? null
  const deliveryBox = item.delivery_box_code
  // "2 of 5" — only meaningful for multi-item orders the board eager-loaded.
  const siblingLabel =
    item.sibling_index != null && item.sibling_count != null && item.sibling_count > 1
      ? `${item.sibling_index}/${item.sibling_count}`
      : null

  async function handleConfirm(payload: StageMovePayload) {
    if (!pendingTransition) return
    try {
      await transition.mutateAsync({ to: pendingTransition, ...payload })
      toast.success(`Moved to ${getStageLabel(pendingTransition)}`)
      setPendingTransition(null)
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Transition failed')
    }
  }

  async function handleHold(reason?: string) {
    try {
      await hold.mutateAsync({ reason: reason ?? '' })
      toast.success('Item placed on hold')
      setShowHold(false)
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Could not place on hold')
    }
  }

  async function handleResume() {
    try {
      await resume.mutateAsync()
      toast.success('Item resumed')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Could not resume')
    }
  }

  const secondaryCls =
    'inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-lg ' +
    'border border-[var(--color-border)] text-[var(--color-text-secondary)] ' +
    'hover:bg-[var(--color-surface-alt)] transition-colors disabled:opacity-50'

  return (
    <>
      <motion.div
        layoutId={`card-${item.id}`}
        draggable={draggable}
        onDragStart={draggable ? () => onDragStartItem?.(item) : undefined}
        onDragEnd={draggable ? () => onDragEndItem?.() : undefined}
        className={cn(
          'bg-white rounded-xl border p-3 cursor-pointer hover:shadow-sm transition-all',
          draggable && 'cursor-grab active:cursor-grabbing',
          dimmed && 'opacity-40',
          highlighted && 'ring-2 ring-[var(--color-brand)] ring-offset-1',
          isOverdue && 'border-l-4 border-l-red-400 border-[var(--color-border)]',
          isOnHold && !isOverdue && 'border-l-4 border-l-slate-400 border-[var(--color-border)]',
          isRework && !isOverdue && !isOnHold && 'border-l-4 border-l-[var(--color-brand)] border-[var(--color-border)]',
          !isOverdue && !isRework && !isOnHold && 'border-[var(--color-border)]',
        )}
        onClick={() => onCardClick?.(item)}
      >
        <div className="space-y-2">
          {/* Header — order #, item code, customer, priority/holds/overdue badges */}
          <div className="flex items-start justify-between gap-2">
            <div className="min-w-0">
              <div className="flex items-center gap-1.5">
                {draggable && (
                  <GripVertical size={13} className="shrink-0 text-[var(--color-text-muted)]" aria-hidden />
                )}
                {/* Order code — opens the read-only order thread (all siblings). */}
                <button
                  type="button"
                  onClick={(e) => { e.stopPropagation(); setShowThread(true) }}
                  className="truncate font-mono text-xs font-semibold text-[var(--color-brand)] hover:underline"
                >
                  {item.order_number ?? `#${item.id}`}
                </button>
                {siblingLabel && (
                  <span className="shrink-0 rounded bg-[var(--color-surface-alt)] px-1 py-0.5 text-[10px] font-semibold tabular-nums text-[var(--color-text-secondary)]">
                    {siblingLabel}
                  </span>
                )}
                {siblingLabel && onToggleHighlight && item.order_id != null && (
                  <button
                    type="button"
                    onClick={(e) => { e.stopPropagation(); onToggleHighlight(item.order_id) }}
                    title="Highlight all items in this order"
                    className={cn(
                      'shrink-0 rounded p-0.5 transition-colors hover:bg-[var(--color-brand-light)]',
                      highlighted ? 'text-[var(--color-brand)]' : 'text-[var(--color-text-muted)]',
                    )}
                  >
                    <Layers size={12} strokeWidth={2} />
                  </button>
                )}
              </div>
              <p className="text-sm font-medium text-[var(--color-text-primary)] truncate max-w-[150px]">
                {item.customer_name ?? '—'}
              </p>
              {item.item_code && (
                <p className="font-mono text-[10px] text-[var(--color-text-muted)] truncate">
                  {item.item_code}
                </p>
              )}
            </div>
            <div className="flex flex-col items-end gap-1 shrink-0">
              {showPriority && (
                <span className={cn('px-1.5 py-0.5 text-[11px] font-semibold rounded-full whitespace-nowrap', PRIORITY_CHIP[priority])}>
                  {priorityLabel(priority)}
                </span>
              )}
              {isOnHold && (
                <span className="inline-flex items-center gap-1 px-1.5 py-0.5 text-[11px] font-medium bg-slate-100 text-slate-600 rounded-full">
                  <PauseCircle size={11} /> Hold
                </span>
              )}
              {isOverdue && (
                <span className="px-1.5 py-0.5 text-[11px] font-medium bg-red-50 text-red-600 rounded-full">
                  Overdue{item.overdue_days ? ` ${item.overdue_days}d` : ''}
                </span>
              )}
              {isRework && (
                <span className="px-1.5 py-0.5 text-[11px] font-medium bg-[var(--color-brand-light)] text-[var(--color-brand)] rounded-full whitespace-nowrap">
                  Rework #{item.rework_count}
                </span>
              )}
            </div>
          </div>

          {/* Stage + garment/qty */}
          <div className="flex flex-wrap items-center gap-1.5">
            <span className="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700">
              {getStageLabel(item.production_state)}
            </span>
            <span className="text-xs text-[var(--color-text-muted)]">
              {item.garment_type ?? 'Garment'}
              {item.quantity ? ` · ${item.quantity} pc` : ''}
              {item.assigned_tailor_name ? ` · ${item.assigned_tailor_name}` : ''}
            </span>
            {deliveryBox && (
              <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700">
                <PackageCheck size={11} strokeWidth={1.75} /> Box {deliveryBox}
              </span>
            )}
          </div>

          {/* Note preview */}
          {item.note_preview && (
            <p className="text-[11px] text-[var(--color-text-muted)] line-clamp-2 italic">
              “{item.note_preview}”
            </p>
          )}

          {/* Meta row — supervisor, deadline, last updated, issues */}
          {(supervisor || deadline || updatedAgo || issueCount > 0) && (
            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-[var(--color-text-muted)]">
              {supervisor && (
                <span className="inline-flex items-center gap-1">
                  <UserRound size={11} /> {supervisor}
                </span>
              )}
              {deadline && (
                <span className={cn('inline-flex items-center gap-1', isOverdue && 'text-red-600 font-medium')}>
                  <CalendarClock size={11} /> {deadline}
                </span>
              )}
              {updatedAgo && (
                <span className="inline-flex items-center gap-1">
                  <Clock size={11} /> {updatedAgo}
                </span>
              )}
              {issueCount > 0 && (
                <span className="inline-flex items-center gap-1 text-amber-600 font-medium">
                  <AlertTriangle size={11} /> {issueCount} issue{issueCount > 1 ? 's' : ''}
                </span>
              )}
            </div>
          )}

          {/* Stage actions */}
          {transitions.length > 0 && (
            <div className="flex flex-wrap gap-1.5 pt-1" onClick={(e) => e.stopPropagation()}>
              {transitions.map((t) => (
                <button
                  key={t}
                  onClick={() => setPendingTransition(t)}
                  disabled={transition.isPending}
                  className="px-2 py-1 text-xs font-medium border border-[var(--color-brand)] text-[var(--color-brand)] rounded-lg hover:bg-[var(--color-brand-light)] transition-colors disabled:opacity-50"
                >
                  → {getStageLabel(t)}
                </button>
              ))}
            </div>
          )}

          {/* Secondary actions — timeline (always) / report issue / hold-resume */}
          <div className="flex flex-wrap gap-1.5 pt-0.5" onClick={(e) => e.stopPropagation()}>
            <button onClick={() => setShowTimeline(true)} className={secondaryCls}>
              <History size={11} /> Timeline
            </button>
            {canReportIssue && (
                <button onClick={() => setShowIssue(true)} className={secondaryCls}>
                  <Flag size={11} /> Issue
                </button>
              )}
              {canHold && (isOnHold ? (
                <button onClick={handleResume} disabled={resume.isPending} className={secondaryCls}>
                  <Play size={11} /> Resume
                </button>
              ) : (
                <button onClick={() => setShowHold(true)} disabled={hold.isPending} className={secondaryCls}>
                  <PauseCircle size={11} /> Hold
                </button>
              ))}
          </div>
        </div>
      </motion.div>

      <StageMoveDialog
        open={pendingTransition !== null}
        onClose={() => setPendingTransition(null)}
        onConfirm={handleConfirm}
        toState={pendingTransition ?? ''}
        toLabel={getStageLabel(pendingTransition ?? '')}
        fromLabel={getStageLabel(item.production_state)}
        quantity={item.quantity ?? 1}
        customerName={item.customer_name}
        currentBox={item.delivery_box_code}
        loading={transition.isPending}
      />

      <ReportIssueModal
        open={showIssue}
        onClose={() => setShowIssue(false)}
        itemId={item.id}
        customerName={item.customer_name}
      />

      <TimelineModal
        open={showTimeline}
        onClose={() => setShowTimeline(false)}
        itemId={item.id}
        title={`${item.order_number ?? `#${item.id}`} · ${item.customer_name ?? ''}`}
      />

      <ConfirmDialog
        open={showHold}
        onClose={() => setShowHold(false)}
        onConfirm={handleHold}
        title="Put item on hold"
        description={`Pause work on ${item.customer_name ?? 'this item'}? It keeps its current stage.`}
        variant="warning"
        confirmLabel="Put on hold"
        requireReason
        reasonLabel="Hold reason"
        loading={hold.isPending}
      />

      {item.order_id != null && (
        <OrderThreadDrawer
          open={showThread}
          onClose={() => setShowThread(false)}
          orderId={item.order_id}
          focusItemId={item.id}
        />
      )}
    </>
  )
}
