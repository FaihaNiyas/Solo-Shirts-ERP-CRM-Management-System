'use client'

import { useState } from 'react'
import { Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { ModalDialog } from '@/components/ui/modal-dialog'
import {
  ALTERATION_ACTION_LABELS,
  ALTERATION_STATUS_LABELS,
  useUpdateAlterationStatus,
} from '@/lib/api/hooks/useAlterations'

/**
 * Renders one action button per permission-filtered allowed-next status. The
 * server already hides transitions the user cannot perform, so we simply render
 * what it sends. Clicking opens a confirm modal with optional notes.
 */
export function AlterationStatusActions({
  alterationId,
  currentStatus,
  allowedNext,
}: {
  alterationId: number
  currentStatus: string
  allowedNext: string[]
}) {
  const update = useUpdateAlterationStatus(alterationId)
  const [target, setTarget] = useState<string | null>(null)
  const [notes, setNotes] = useState('')

  if (allowedNext.length === 0) return null

  function open(status: string) {
    setTarget(status)
    setNotes('')
  }

  async function confirm() {
    if (!target) return
    try {
      await update.mutateAsync({ status: target, notes: notes.trim() || undefined })
      toast.success(`Alteration marked ${ALTERATION_STATUS_LABELS[target] ?? target}`)
      setTarget(null)
    } catch (err: unknown) {
      const e = err as { message?: string }
      toast.error(e?.message ?? 'Could not update status')
    }
  }

  return (
    <div className="flex flex-wrap gap-2">
      {allowedNext.map((status) => {
        const danger = status === 'cancelled'
        return (
          <button
            key={status}
            type="button"
            onClick={() => open(status)}
            className={
              danger
                ? 'inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 h-9 text-sm font-medium text-[var(--color-danger)] hover:bg-red-50 transition-colors'
                : 'inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-3 h-9 text-sm font-medium text-white hover:bg-[var(--color-brand-dark)] transition-colors'
            }
          >
            {ALTERATION_ACTION_LABELS[status] ?? `Mark ${ALTERATION_STATUS_LABELS[status] ?? status}`}
          </button>
        )
      })}

      <ModalDialog
        open={target !== null}
        onClose={() => setTarget(null)}
        title={target ? (ALTERATION_ACTION_LABELS[target] ?? 'Update status') : 'Update status'}
        description={
          target
            ? `Move this customer alteration from ${ALTERATION_STATUS_LABELS[currentStatus] ?? currentStatus} to ${ALTERATION_STATUS_LABELS[target] ?? target}.`
            : undefined
        }
        size="sm"
      >
        <div className="space-y-4">
          <div className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
            This updates the customer alteration only. It does not change the original order invoice or
            production state.
          </div>

          <label className="block">
            <span className="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">
              Notes <span className="font-normal text-[var(--color-text-muted)]">(optional)</span>
            </span>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              rows={3}
              maxLength={1000}
              placeholder="e.g. Customer approved the estimate."
              className="w-full px-3 py-2.5 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white resize-none focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </label>

          <div className="flex justify-end gap-2">
            <button
              type="button"
              onClick={() => setTarget(null)}
              className="px-4 h-10 rounded-lg border border-[var(--color-border-mid)] text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              onClick={confirm}
              disabled={update.isPending}
              className="inline-flex items-center gap-1.5 px-5 h-10 rounded-lg bg-[var(--color-brand)] text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            >
              {update.isPending && <Loader2 size={15} className="animate-spin" />}
              Confirm
            </button>
          </div>
        </div>
      </ModalDialog>
    </div>
  )
}
