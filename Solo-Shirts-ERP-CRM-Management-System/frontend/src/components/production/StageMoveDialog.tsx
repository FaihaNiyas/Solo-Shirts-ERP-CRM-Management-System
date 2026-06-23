'use client'

import { useEffect, useState } from 'react'
import { ArrowRight } from 'lucide-react'
import { ModalDialog } from '@/components/ui/modal-dialog'
import { FormField, Input, Textarea, Button } from '@/components/ui/form-field'

export interface StageMovePayload {
  completed_qty?: number
  rejected_qty?: number
  notes?: string
  delivery_box_code?: string
}

interface Props {
  open: boolean
  onClose: () => void
  onConfirm: (payload: StageMovePayload) => Promise<void> | void
  /** PascalCase target state (e.g. 'Tailoring'). */
  toState: string
  toLabel: string
  fromLabel: string
  quantity: number
  customerName?: string | null
  /** Current delivery box on the item (prefills the box field on a re-move). */
  currentBox?: string | null
  loading?: boolean
}

// The backend mandates a reason when sending an item back for rework or cancelling.
const REASON_REQUIRED = new Set(['Rework', 'Cancelled'])

// Staging for pickup: capture the delivery box / shelf number on these moves.
const DELIVERY_STAGES = new Set(['ReadyForDelivery', 'Delivered'])

/**
 * The "complete stage" confirmation. Kept deliberately minimal: a single-piece
 * garment just needs the confirmation checkbox. The piece-count inputs appear only
 * for multi-piece items, and the reason box only when the move requires one
 * (rework / cancel). No attachments or free-text notes on a normal forward move.
 */
export function StageMoveDialog({
  open,
  onClose,
  onConfirm,
  toState,
  toLabel,
  fromLabel,
  quantity,
  customerName,
  currentBox,
  loading = false,
}: Props) {
  const reasonRequired = REASON_REQUIRED.has(toState)
  const showQty = quantity > 1
  const showBox = DELIVERY_STAGES.has(toState)

  const [completed, setCompleted] = useState(String(quantity))
  const [rejected, setRejected] = useState('0')
  const [notes, setNotes] = useState('')
  const [box, setBox] = useState('')
  const [confirmed, setConfirmed] = useState(false)

  // Reset the form each time the dialog (re)opens for a target stage.
  useEffect(() => {
    if (open) {
      setCompleted(String(quantity))
      setRejected('0')
      setNotes('')
      setBox(currentBox ?? '')
      setConfirmed(false)
    }
  }, [open, quantity, toState, currentBox])

  const completedNum = Number(completed)
  const rejectedNum = Number(rejected)
  const qtyValid =
    Number.isFinite(completedNum) && completedNum >= 0 && completedNum <= quantity &&
    Number.isFinite(rejectedNum) && rejectedNum >= 0 && rejectedNum <= quantity
  const notesValid = !reasonRequired || notes.trim().length > 0
  const canSubmit = confirmed && qtyValid && notesValid && !loading

  async function submit() {
    if (!canSubmit) return
    await onConfirm({
      completed_qty: Number.isFinite(completedNum) ? completedNum : undefined,
      rejected_qty: Number.isFinite(rejectedNum) ? rejectedNum : undefined,
      notes: notes.trim() || undefined,
      delivery_box_code: showBox ? box.trim() || undefined : undefined,
    })
  }

  return (
    <ModalDialog
      open={open}
      onClose={onClose}
      title={`Move to ${toLabel}`}
      description={`${customerName ?? 'Item'} · ${fromLabel} → ${toLabel}`}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={loading}>
            Cancel
          </Button>
          <Button onClick={submit} disabled={!canSubmit} loading={loading}>
            Confirm &amp; move
          </Button>
        </>
      }
    >
      <div className="space-y-4">
        {/* Stage flow — makes the move feel deliberate: where it is → where it goes. */}
        <div className="flex items-center justify-center gap-2 rounded-xl bg-[var(--color-surface-alt)] px-3 py-3">
          <span className="rounded-full border border-[var(--color-border-mid)] bg-white px-3 py-1 text-xs font-medium text-[var(--color-text-secondary)]">
            {fromLabel}
          </span>
          <ArrowRight size={16} strokeWidth={2} className="shrink-0 text-[var(--color-brand)]" />
          <span className="rounded-full bg-[var(--color-brand-light)] px-3 py-1 text-xs font-semibold text-[var(--color-brand)]">
            {toLabel}
          </span>
        </div>

        {showQty && (
          <div className="grid grid-cols-2 gap-3">
            <FormField label="Completed qty" hint={`of ${quantity}`}>
              <Input
                type="number"
                min={0}
                max={quantity}
                value={completed}
                onChange={(e) => setCompleted(e.target.value)}
                error={!qtyValid}
              />
            </FormField>
            <FormField label="Rejected / damaged">
              <Input
                type="number"
                min={0}
                max={quantity}
                value={rejected}
                onChange={(e) => setRejected(e.target.value)}
                error={!qtyValid}
              />
            </FormField>
          </div>
        )}

        {showBox && (
          <FormField label="Delivery box no." hint="Where the finished package is kept — searched at the Front Desk on pickup.">
            <Input
              type="text"
              value={box}
              onChange={(e) => setBox(e.target.value)}
              placeholder="e.g. B-12"
            />
          </FormField>
        )}

        {reasonRequired && (
          <FormField label="Reason" required hint="Required for rework / cancellation.">
            <Textarea
              rows={3}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Why is this item being sent back…"
              error={!notesValid}
            />
          </FormField>
        )}

        <label
          className={
            'flex items-start gap-2.5 cursor-pointer select-none rounded-xl border p-3 transition-colors ' +
            (confirmed
              ? 'border-[var(--color-brand)] bg-[var(--color-brand-light)]/40'
              : 'border-[var(--color-border-mid)] hover:bg-[var(--color-surface-alt)]')
          }
        >
          <input
            type="checkbox"
            checked={confirmed}
            onChange={(e) => setConfirmed(e.target.checked)}
            className="mt-0.5 h-4 w-4 rounded border-[var(--color-border-mid)] text-[var(--color-brand)] focus:ring-[var(--color-brand)]"
          />
          <span className="text-sm text-[var(--color-text-secondary)]">
            I confirm this stage is completed{showQty ? ' and the counts above are correct' : ''}.
          </span>
        </label>
      </div>
    </ModalDialog>
  )
}
