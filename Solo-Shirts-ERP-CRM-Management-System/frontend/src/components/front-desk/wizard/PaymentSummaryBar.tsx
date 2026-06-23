'use client'

import { cn, formatINR } from '@/lib/utils'
import { PAYMENT_METHOD_LABELS } from './types'
import { useWizard } from './WizardContext'

/**
 * Sticky payment summary shown across the wizard. Phase 1 is UI-only draft
 * state — nothing is submitted to the finance API (Front Desk also lacks the
 * finance.payment.record permission until Phase 2).
 */
export function PaymentSummaryBar() {
  const { payment, grandTotal, balance } = useWizard()
  const { advancePaid, method } = payment

  // Pre-confirm the payment is always a draft; it is recorded only on confirm.
  const status = grandTotal <= 0 ? 'Draft' : advancePaid > 0 ? 'Will record on confirm' : 'Draft'

  return (
    <div className="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl border border-[var(--color-border)] bg-white px-4 py-2.5">
      <Stat label="Total" value={formatINR(grandTotal)} />
      <Stat label="Advance" value={formatINR(advancePaid)} tone="success" />
      <Stat label="Balance" value={formatINR(balance)} tone={balance > 0 ? 'warning' : 'muted'} />
      <Stat label="Method" value={method ? PAYMENT_METHOD_LABELS[method] : '—'} />
      <div className="ml-auto flex items-center gap-2">
        <span className="text-[11px] uppercase tracking-wide text-[var(--color-text-muted)]">Payment</span>
        <span
          className={cn(
            'rounded-full px-2.5 py-0.5 text-xs font-medium',
            status === 'Will record on confirm' ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-500',
          )}
        >
          {status}
        </span>
      </div>
    </div>
  )
}

function Stat({
  label,
  value,
  tone = 'default',
}: {
  label: string
  value: string
  tone?: 'default' | 'success' | 'warning' | 'muted'
}) {
  return (
    <div className="flex flex-col">
      <span className="text-[11px] uppercase tracking-wide text-[var(--color-text-muted)]">{label}</span>
      <span
        className={cn(
          'text-sm font-semibold tabular-nums',
          tone === 'default' && 'text-[var(--color-text-primary)]',
          tone === 'success' && 'text-[var(--color-success)]',
          tone === 'warning' && 'text-[var(--color-warning)]',
          tone === 'muted' && 'text-[var(--color-text-muted)]',
        )}
      >
        {value}
      </span>
    </div>
  )
}
