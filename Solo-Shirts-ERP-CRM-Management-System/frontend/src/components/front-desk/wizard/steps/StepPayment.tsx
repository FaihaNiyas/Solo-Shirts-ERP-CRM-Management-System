'use client'

import { memo } from 'react'
import { Info } from 'lucide-react'
import { SectionCard } from '@/components/ui/section-card'
import { FormField } from '@/components/ui/form-field'
import { cn, formatINR } from '@/lib/utils'
import { PAYMENT_METHOD_LABELS, type PaymentMethod, type SubOrderDraft } from '../types'
import { GST_RATE_OPTIONS, lineDiscountExceeds, lineTotals, orderTotals } from '../pricing'
import { useWizard } from '../WizardContext'

const numCls =
  'w-full h-9 px-2 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]'

export function StepPayment() {
  const { subOrders, patchSubOrder, payment, patchPayment, grandTotal, balance } = useWizard()
  const totals = orderTotals(subOrders)

  const advanceExceeds = payment.advancePaid > grandTotal
  const methodMissing = payment.advancePaid > 0 && !payment.method
  const showReference = payment.method === 'upi' || payment.method === 'bank_transfer'

  return (
    <div className="space-y-4">
      <SectionCard title="Per-shirt pricing">
        <div className="mb-3 flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2.5 text-xs text-blue-700">
          <Info size={14} strokeWidth={1.75} className="mt-0.5 shrink-0" />
          <span>Price each shirt. GST is calculated by the system on confirm; totals here are a live preview.</span>
        </div>

        <div className="space-y-2">
          {subOrders.map((s, i) => (
            <PricingRow key={s.tempId} index={i} sub={s} onPatch={patchSubOrder} />
          ))}
        </div>

        {/* Totals */}
        <div className="mt-4 ml-auto max-w-xs space-y-1 text-sm">
          <TotalRow label="Items" value={formatINR(totals.grossPaise / 100)} />
          <TotalRow label="Discount" value={`− ${formatINR(totals.discountPaise / 100)}`} />
          <TotalRow label="Taxable" value={formatINR(totals.taxablePaise / 100)} />
          <TotalRow label="GST" value={formatINR(totals.gstPaise / 100)} />
          <div className="border-t border-[var(--color-border)] pt-1">
            <TotalRow label="Grand total" value={formatINR(totals.grandPaise / 100)} strong />
          </div>
        </div>
      </SectionCard>

      <SectionCard title="Advance payment">
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <FormField label="Advance (₹)" error={advanceExceeds ? 'Advance cannot exceed the grand total.' : undefined}>
            <input
              type="number"
              min={0}
              value={payment.advancePaid || ''}
              onChange={(e) => patchPayment({ advancePaid: Math.max(0, parseFloat(e.target.value) || 0) })}
              className={cn(numCls, 'h-10')}
              placeholder="0"
            />
          </FormField>

          <FormField label="Method" error={methodMissing ? 'Required when an advance is entered.' : undefined}>
            <select
              value={payment.method ?? ''}
              onChange={(e) => patchPayment({ method: (e.target.value || null) as PaymentMethod | null })}
              className={cn(numCls, 'h-10')}
            >
              <option value="">Select…</option>
              {(Object.keys(PAYMENT_METHOD_LABELS) as PaymentMethod[]).map((m) => (
                <option key={m} value={m}>
                  {PAYMENT_METHOD_LABELS[m]}
                </option>
              ))}
            </select>
          </FormField>

          {showReference && (
            <FormField label="Reference">
              <input
                value={payment.reference}
                onChange={(e) => patchPayment({ reference: e.target.value })}
                className={cn(numCls, 'h-10')}
                placeholder="UPI / bank ref"
              />
            </FormField>
          )}
        </div>

        <div className="mt-4 flex items-center justify-between rounded-lg bg-[var(--color-surface-alt)] px-4 py-3">
          <span className="text-sm text-[var(--color-text-secondary)]">Balance after advance</span>
          <span className={`text-lg font-semibold tabular-nums ${balance > 0 ? 'text-[var(--color-warning)]' : 'text-[var(--color-success)]'}`}>
            {formatINR(balance)}
          </span>
        </div>
        <p className="mt-2 text-xs text-[var(--color-text-muted)]">Payment is recorded on confirmation.</p>
      </SectionCard>
    </div>
  )
}

const PricingRow = memo(function PricingRow({
  index,
  sub,
  onPatch,
}: {
  index: number
  sub: SubOrderDraft
  // Stable callback (takes tempId) so each row can be memoized.
  onPatch: (tempId: string, patch: Partial<SubOrderDraft>) => void
}) {
  const l = lineTotals(sub)
  const overDiscount = lineDiscountExceeds(sub)
  const bits = [sub.fabricLabel, sub.styleLabel, sub.fitLabel].filter(Boolean).join(' / ')

  return (
    <div className="rounded-xl border border-[var(--color-border)] p-3">
      <div className="flex items-center justify-between mb-2">
        <span className="text-sm font-semibold text-[var(--color-text-primary)]">
          <span className="ss-mono">Shirt {String(index + 1).padStart(2, '0')}</span>
          {bits && <span className="ml-2 text-xs font-normal text-[var(--color-text-muted)]">{bits}</span>}
        </span>
        <span className="text-sm font-semibold tabular-nums text-[var(--color-text-primary)]">{formatINR(l.totalPaise / 100)}</span>
      </div>
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <Num label="Base" value={sub.basePrice} onChange={(v) => onPatch(sub.tempId, { basePrice: v })} />
        <Num label="Discount −" value={sub.discountAmount} onChange={(v) => onPatch(sub.tempId, { discountAmount: v })} error={overDiscount} />
        <label className="block">
          <span className="block text-[11px] text-[var(--color-text-muted)] mb-1">GST %</span>
          <select
            value={sub.gstRate}
            onChange={(e) => onPatch(sub.tempId, { gstRate: Number(e.target.value) })}
            className={numCls}
          >
            {GST_RATE_OPTIONS.map((r) => (
              <option key={r} value={r}>
                {r}%
              </option>
            ))}
          </select>
        </label>
      </div>
      {overDiscount && (
        <p className="mt-1 text-xs text-[var(--color-warning)]">
          Discount is more than this shirt&apos;s price — it&apos;s capped, so this shirt is free.
        </p>
      )}
    </div>
  )
})

function Num({
  label,
  value,
  onChange,
  error,
}: {
  label: string
  value: number
  onChange: (v: number) => void
  error?: boolean
}) {
  return (
    <label className="block">
      <span className="block text-[11px] text-[var(--color-text-muted)] mb-1">{label}</span>
      <input
        type="number"
        min={0}
        value={value || ''}
        onChange={(e) => onChange(Math.max(0, parseFloat(e.target.value) || 0))}
        className={cn(numCls, error && 'border-[var(--color-danger)]')}
        placeholder="0"
      />
    </label>
  )
}

function TotalRow({ label, value, strong }: { label: string; value: string; strong?: boolean }) {
  return (
    <div className="flex items-center justify-between">
      <span className={strong ? 'font-semibold text-[var(--color-text-primary)]' : 'text-[var(--color-text-muted)]'}>{label}</span>
      <span className={cn('tabular-nums', strong ? 'text-base font-bold text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)]')}>
        {value}
      </span>
    </div>
  )
}
