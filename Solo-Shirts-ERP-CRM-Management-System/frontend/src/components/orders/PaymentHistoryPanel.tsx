'use client'

import { format } from 'date-fns'
import { formatINR } from '@/lib/utils'
import { PAYMENT_METHOD_LABELS } from '@/components/front-desk/wizard/types'
import type { OrderPaymentRow } from '@/lib/api/hooks/useOrderPayments'

export function PaymentHistoryPanel({ payments }: { payments: OrderPaymentRow[] }) {
  if (payments.length === 0) {
    return <p className="py-4 text-center text-sm text-[var(--color-text-muted)]">No payments recorded yet.</p>
  }

  return (
    <div className="overflow-hidden rounded-xl border border-[var(--color-border)]">
      <table className="w-full text-sm">
        <thead>
          <tr className="bg-[var(--color-surface-alt)] text-[11px] uppercase tracking-wide text-[var(--color-text-muted)]">
            <th className="px-3 py-2 text-left font-semibold">Date</th>
            <th className="px-3 py-2 text-right font-semibold">Amount</th>
            <th className="px-3 py-2 text-left font-semibold">Method</th>
            <th className="px-3 py-2 text-left font-semibold">Reference</th>
            <th className="px-3 py-2 text-left font-semibold">Recorded by</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-[var(--color-border)] bg-white">
          {payments.map((p) => (
            <tr key={p.id}>
              <td className="px-3 py-2 text-[var(--color-text-secondary)]">
                {p.paid_at ? format(new Date(p.paid_at), 'dd MMM yyyy, HH:mm') : '—'}
              </td>
              <td className="px-3 py-2 text-right font-medium tabular-nums text-[var(--color-text-primary)]">
                {formatINR(p.amount)}
              </td>
              <td className="px-3 py-2 text-[var(--color-text-secondary)]">
                {PAYMENT_METHOD_LABELS[p.method as keyof typeof PAYMENT_METHOD_LABELS] ?? p.method}
              </td>
              <td className="px-3 py-2 text-[var(--color-text-muted)]">{p.reference ?? '—'}</td>
              <td className="px-3 py-2 text-[var(--color-text-muted)]">{p.recorded_by ?? '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
