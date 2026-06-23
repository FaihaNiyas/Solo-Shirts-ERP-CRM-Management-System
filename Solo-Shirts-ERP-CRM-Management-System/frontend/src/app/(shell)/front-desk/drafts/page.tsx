'use client'

import { useState } from 'react'
import Link from 'next/link'
import { format } from 'date-fns'
import { AlertTriangle, FileStack, Loader2, PlayCircle, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { PageHeader } from '@/components/ui/page-header'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { useFrontDeskDrafts, useDiscardDraft, type DraftRow } from '@/lib/api/hooks/useFrontDeskDrafts'

const STEP_LABELS: Record<string, string> = {
  customer: 'Customer',
  member: 'Member',
  main: 'Order details',
  subOrders: 'Sub-orders',
  print: 'Boxes & print',
  payment: 'Pricing & payment',
  review: 'Review',
}

export default function FrontDeskDraftsPage() {
  const { data, isLoading, isError, error } = useFrontDeskDrafts()
  const discard = useDiscardDraft()
  const err = error as { message?: string; request_id?: string } | null
  const [showClearAll, setShowClearAll] = useState(false)
  const [clearing, setClearing] = useState(false)

  async function onDiscard(d: DraftRow) {
    try {
      await discard.mutateAsync(d.id)
      toast.success('Draft discarded' + (d.order_id ? ' — intake order cancelled, boxes released.' : '.'))
    } catch (e: unknown) {
      toast.error((e as { message?: string })?.message ?? 'Could not discard the draft.')
    }
  }

  // Discard every draft. Some carry intake orders, so this cancels those orders
  // and releases their boxes — hence the explicit confirmation.
  async function onClearAll() {
    if (!data || data.length === 0) return
    setClearing(true)
    const results = await Promise.allSettled(data.map((d) => discard.mutateAsync(d.id)))
    setClearing(false)
    setShowClearAll(false)

    const failed = results.filter((r) => r.status === 'rejected').length
    if (failed === 0) {
      toast.success(`Cleared all ${data.length} draft${data.length > 1 ? 's' : ''}.`)
    } else {
      toast.error(`${failed} of ${data.length} drafts could not be cleared.`)
    }
  }

  const hasDrafts = !!data && data.length > 0

  return (
    <div className="space-y-5">
      <PageHeader
        title="Saved Drafts"
        description="Paused orders you can resume from any counter"
        actions={
          <div className="flex items-center gap-2">
            {hasDrafts && (
              <button
                type="button"
                onClick={() => setShowClearAll(true)}
                disabled={clearing || discard.isPending}
                className="inline-flex items-center gap-1.5 rounded-xl border border-red-200 px-4 h-10 text-sm font-semibold text-[var(--color-danger)] hover:bg-red-50 disabled:opacity-50"
              >
                <Trash2 size={15} strokeWidth={1.75} /> Clear all
              </button>
            )}
            <Link href="/front-desk/new" className="inline-flex items-center gap-2 rounded-xl bg-[var(--color-brand)] px-4 h-10 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)]">
              New Order
            </Link>
          </div>
        }
      />

      {isLoading && (
        <div className="flex items-center justify-center gap-2 py-12 text-sm text-[var(--color-text-muted)]">
          <Loader2 size={16} className="animate-spin" /> Loading…
        </div>
      )}

      {isError && (
        <div className="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          <AlertTriangle size={16} strokeWidth={1.75} className="mt-0.5 shrink-0" />
          <div>
            <p>{err?.message ?? 'Could not load drafts.'}</p>
            {err?.request_id && <p className="text-xs opacity-75">request_id: {err.request_id}</p>}
          </div>
        </div>
      )}

      {!isLoading && !isError && data && data.length === 0 && (
        <div className="flex flex-col items-center gap-2 py-12 text-center text-sm text-[var(--color-text-muted)]">
          <FileStack size={26} strokeWidth={1.5} />
          No saved drafts. Start a new order and use “Save Draft &amp; Pause”.
        </div>
      )}

      {data && data.length > 0 && (
        <div className="space-y-3">
          {data.map((d) => (
            <div key={d.id} className="flex flex-wrap items-center gap-3 rounded-2xl border border-[var(--color-border)] bg-white p-4">
              <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold text-[var(--color-text-primary)] truncate">
                  {d.title ?? d.customer_name ?? 'Untitled draft'}
                  {d.status === 'paused' && <span className="ml-2 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800">Paused</span>}
                  {d.order_id && <span className="ml-2 rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700">Intake order</span>}
                </p>
                <p className="mt-0.5 text-xs text-[var(--color-text-muted)]">
                  {STEP_LABELS[d.current_step ?? ''] ?? d.current_step ?? 'In progress'} · {d.completed_count}/{d.total_items} complete
                  {d.created_by ? ` · ${d.created_by}` : ''}
                  {d.last_saved_at ? ` · saved ${format(new Date(d.last_saved_at), 'dd MMM, HH:mm')}` : ''}
                </p>
              </div>
              <Link
                href={`/front-desk/new?draft=${d.id}`}
                className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-3 h-9 text-sm font-medium text-white hover:bg-[var(--color-brand-dark)]"
              >
                <PlayCircle size={15} strokeWidth={1.75} /> Resume
              </Link>
              <button
                type="button"
                onClick={() => onDiscard(d)}
                disabled={discard.isPending}
                className="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 h-9 text-sm font-medium text-[var(--color-danger)] hover:bg-red-50 disabled:opacity-50"
              >
                <Trash2 size={15} strokeWidth={1.75} /> Discard
              </button>
            </div>
          ))}
        </div>
      )}

      <ConfirmDialog
        open={showClearAll}
        onClose={() => setShowClearAll(false)}
        onConfirm={onClearAll}
        title="Clear all drafts?"
        description={`This discards ${data?.length ?? 0} draft${(data?.length ?? 0) > 1 ? 's' : ''}. Any intake orders are cancelled and their boxes released. This cannot be undone.`}
        variant="danger"
        confirmLabel="Clear all"
        loading={clearing}
      />
    </div>
  )
}
