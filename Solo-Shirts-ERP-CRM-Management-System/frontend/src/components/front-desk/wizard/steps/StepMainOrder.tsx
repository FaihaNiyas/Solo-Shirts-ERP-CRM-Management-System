'use client'

import { useEffect, useState } from 'react'
import { Lock } from 'lucide-react'
import { SectionCard } from '@/components/ui/section-card'
import { FormField } from '@/components/ui/form-field'
import { cn } from '@/lib/utils'
import {
  DELIVERY_MODE_LABELS,
  SOURCE_LABELS,
  type DeliveryMode,
  type OrderSource,
} from '../types'
import { useWizard } from '../WizardContext'

const inputCls =
  'w-full h-10 px-3 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]'

export function StepMainOrder() {
  const { meta, patchMeta, setTotalShirts } = useWizard()

  // The order date is the intake moment — always "now", never user-edited. We
  // show a live date+time label (refreshed each minute) and keep meta.orderDate
  // pinned to today so the delivery-date floor stays correct. The backend stamps
  // the authoritative created_at on save.
  const [nowLabel, setNowLabel] = useState('')
  useEffect(() => {
    const tick = () => {
      const now = new Date()
      setNowLabel(
        now.toLocaleString(undefined, {
          day: '2-digit',
          month: 'short',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        }),
      )
      const today = now.toISOString().slice(0, 10)
      if (meta.orderDate !== today) patchMeta({ orderDate: today })
    }
    tick()
    const id = setInterval(tick, 60_000)
    return () => clearInterval(id)
  }, [meta.orderDate, patchMeta])

  // Delivery must be on/after the order date (no time component in this flow).
  const deliveryBeforeOrder =
    !!meta.orderDate && !!meta.deliveryDate && meta.deliveryDate < meta.orderDate

  return (
    <SectionCard title="Main order details">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FormField label="Order source" required>
          <select
            value={meta.source}
            onChange={(e) => patchMeta({ source: e.target.value as OrderSource })}
            className={inputCls}
          >
            {(Object.keys(SOURCE_LABELS) as OrderSource[]).map((s) => (
              <option key={s} value={s}>
                {SOURCE_LABELS[s]}
              </option>
            ))}
          </select>
        </FormField>

        <FormField label="Delivery mode" required>
          <select
            value={meta.deliveryMode}
            onChange={(e) => patchMeta({ deliveryMode: e.target.value as DeliveryMode })}
            className={inputCls}
          >
            {(Object.keys(DELIVERY_MODE_LABELS) as DeliveryMode[]).map((m) => (
              <option key={m} value={m}>
                {DELIVERY_MODE_LABELS[m]}
              </option>
            ))}
          </select>
        </FormField>

        <FormField label="Order date" hint="Set automatically to the current date and time.">
          <div className="relative">
            <input
              type="text"
              readOnly
              aria-label="Order date and time (auto-set to now)"
              value={nowLabel}
              className={cn(inputCls, 'cursor-not-allowed bg-[var(--color-surface-alt)] pr-9 text-[var(--color-text-secondary)]')}
            />
            <Lock
              size={14}
              strokeWidth={1.75}
              className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[var(--color-text-muted)]"
            />
          </div>
        </FormField>

        <FormField label="Delivery date" required error={deliveryBeforeOrder ? 'Delivery date must be on or after the order date.' : undefined}>
          <input
            type="date"
            value={meta.deliveryDate ?? ''}
            min={meta.orderDate || undefined}
            onChange={(e) => patchMeta({ deliveryDate: e.target.value })}
            className={inputCls}
          />
        </FormField>

        <FormField label="Total shirts" required hint="Creates one sub-order card per shirt (quantity is always 1).">
          <input
            type="number"
            min={1}
            max={50}
            value={meta.totalShirts}
            onChange={(e) => setTotalShirts(parseInt(e.target.value, 10) || 1)}
            className={inputCls}
          />
        </FormField>
      </div>

      <FormField label="Notes" className="mt-4">
        <textarea
          value={meta.notes ?? ''}
          onChange={(e) => patchMeta({ notes: e.target.value })}
          rows={2}
          placeholder="Order-level notes (optional)"
          className="w-full px-3 py-2.5 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] resize-none"
        />
      </FormField>
    </SectionCard>
  )
}
