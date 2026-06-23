'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiMutate, generateIdempotencyKey } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'
import { PageHeader } from '@/components/ui/page-header'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { cn } from '@/lib/utils'

interface RackSlot {
  id: number
  row_number?: number
  slot_number?: number
  is_occupied?: boolean
  order_number?: string
  customer_name?: string
  item_id?: number
}

export default function RackPage() {
  const qc = useQueryClient()
  const { data: slots = [], isLoading } = useQuery<RackSlot[]>({
    queryKey: queryKeys.rackSlots(),
    queryFn: () => apiGet<RackSlot[]>(ENDPOINTS.rackSlots).then((r) => r.data),
  })

  const [selectedSlot, setSelectedSlot] = useState<RackSlot | null>(null)
  const [itemId, setItemId] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function handleAssign() {
    if (!selectedSlot || !itemId) return
    setSubmitting(true)
    try {
      await apiMutate(
        'post',
        ENDPOINTS.assignRack(parseInt(itemId)),
        { slot_id: selectedSlot.id },
        generateIdempotencyKey(),
      )
      qc.invalidateQueries({ queryKey: queryKeys.rackSlots() })
      setSelectedSlot(null)
      setItemId('')
      toast.success(`Item assigned to slot ${selectedSlot.row_number}-${selectedSlot.slot_number}`)
    } catch (err: unknown) {
      const e = err as { code?: string; message?: string }
      if (e?.code === 'RACK_SLOT_OCCUPIED') {
        toast.error('Slot already occupied')
      } else if (e?.code === 'ITEM_ALREADY_ASSIGNED') {
        toast.error('Item is already assigned to another slot')
      } else {
        toast.error(e?.message ?? 'Failed to assign')
      }
    } finally {
      setSubmitting(false)
    }
  }

  const rows = Array.from(new Set(slots.map((s) => s.row_number))).sort((a, b) => (a ?? 0) - (b ?? 0))

  return (
    <div className="space-y-6">
      <PageHeader title="Rack Management" subtitle="Assign items to rack slots" />

      {isLoading ? (
        <div className="h-48 animate-pulse rounded-xl bg-[var(--color-border)]" />
      ) : (
        <div className="space-y-3">
          {rows.map((row) => (
            <div key={row} className="flex items-center gap-2">
              <span className="text-xs font-mono text-[var(--color-text-muted)] w-8">R{row}</span>
              <div className="flex gap-2 flex-wrap">
                {slots
                  .filter((s) => s.row_number === row)
                  .sort((a, b) => (a.slot_number ?? 0) - (b.slot_number ?? 0))
                  .map((slot) => (
                    <button
                      key={slot.id}
                      onClick={() => !slot.is_occupied && setSelectedSlot(slot)}
                      title={slot.is_occupied ? `${slot.order_number} — ${slot.customer_name}` : `Slot ${row}-${slot.slot_number} (empty)`}
                      className={cn(
                        'w-14 h-10 rounded-lg border-2 text-xs font-mono transition-colors',
                        slot.is_occupied
                          ? 'border-[var(--color-brand)] bg-[var(--color-brand-light)] text-[var(--color-brand)] cursor-default'
                          : 'border-dashed border-[var(--color-border-mid)] text-[var(--color-text-muted)] hover:border-[var(--color-brand)] hover:bg-[var(--color-brand-light)] cursor-pointer',
                      )}
                    >
                      {slot.is_occupied ? (
                        <span className="text-[10px] leading-tight px-0.5 truncate">
                          {slot.order_number?.slice(-4) ?? '—'}
                        </span>
                      ) : (
                        <span className="text-[10px]">{slot.slot_number}</span>
                      )}
                    </button>
                  ))}
              </div>
            </div>
          ))}
        </div>
      )}

      <DrawerPanel open={selectedSlot !== null} onClose={() => { setSelectedSlot(null); setItemId('') }} title="Assign to Rack Slot" size="sm">
        <div className="space-y-4 p-4">
          <p className="text-sm text-[var(--color-text-secondary)]">
            Slot: Row {selectedSlot?.row_number}, Position {selectedSlot?.slot_number}
          </p>
          <FormField label="Order Item ID" required>
            <input
              type="number"
              value={itemId}
              onChange={(e) => setItemId(e.target.value)}
              placeholder="Enter item ID from Ready for Delivery"
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </FormField>
          <div className="flex gap-2">
            <button
              onClick={handleAssign}
              disabled={!itemId || submitting}
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {submitting ? 'Assigning…' : 'Assign to Slot'}
            </button>
            <button
              onClick={() => { setSelectedSlot(null); setItemId('') }}
              className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </DrawerPanel>
    </div>
  )
}
