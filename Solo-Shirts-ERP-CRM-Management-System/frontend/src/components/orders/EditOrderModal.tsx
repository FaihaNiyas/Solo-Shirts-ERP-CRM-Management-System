'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { ModalDialog } from '@/components/ui/modal-dialog'
import { FormField } from '@/components/ui/form-field'
import { useUpdateOrder } from '@/lib/api/hooks/useOrders'

interface Props {
  orderId: number
  current: {
    expected_delivery_date?: string | null
    delivery_mode?: string | null
    notes?: string | null
  }
  open: boolean
  onClose: () => void
}

const MODES = [
  { value: 'pickup', label: 'Counter Pickup' },
  { value: 'home', label: 'Home Delivery' },
  { value: 'courier', label: 'Courier' },
]

/** Reschedule the delivery date and edit delivery mode / notes for an order. */
export function EditOrderModal({ orderId, current, open, onClose }: Props) {
  const update = useUpdateOrder(orderId)
  const [date, setDate] = useState(current.expected_delivery_date?.slice(0, 10) ?? '')
  const [mode, setMode] = useState(current.delivery_mode ?? 'pickup')
  const [notes, setNotes] = useState(current.notes ?? '')

  async function save() {
    try {
      await update.mutateAsync({
        expected_delivery_date: date || null,
        delivery_mode: mode,
        notes: notes || null,
      })
      toast.success('Order updated')
      onClose()
    } catch (err) {
      toast.error((err as { message?: string })?.message ?? 'Could not update the order.')
    }
  }

  return (
    <ModalDialog
      open={open}
      onClose={onClose}
      title="Edit order"
      footer={
        <div className="flex justify-end gap-2">
          <button onClick={onClose} className="px-4 h-9 text-sm rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
            Cancel
          </button>
          <button
            onClick={save}
            disabled={update.isPending}
            className="px-5 h-9 text-sm font-medium rounded-lg bg-[var(--color-brand)] text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-50"
          >
            {update.isPending ? 'Saving…' : 'Save'}
          </button>
        </div>
      }
    >
      <div className="space-y-4">
        <FormField label="Delivery date">
          <input
            type="date"
            value={date}
            onChange={(e) => setDate(e.target.value)}
            className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg"
          />
        </FormField>
        <FormField label="Delivery mode">
          <select
            value={mode}
            onChange={(e) => setMode(e.target.value)}
            className="w-full h-9 px-2 text-sm border border-[var(--color-border)] rounded-lg"
          >
            {MODES.map((m) => (
              <option key={m.value} value={m.value}>{m.label}</option>
            ))}
          </select>
        </FormField>
        <FormField label="Notes">
          <textarea
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            rows={3}
            placeholder="Order notes (optional)"
            className="w-full px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg"
          />
        </FormField>
      </div>
    </ModalDialog>
  )
}
