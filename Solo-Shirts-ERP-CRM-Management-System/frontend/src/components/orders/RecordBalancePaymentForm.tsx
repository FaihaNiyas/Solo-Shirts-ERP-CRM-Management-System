'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { FormField } from '@/components/ui/form-field'
import { formatINR } from '@/lib/utils'
import { PAYMENT_METHOD_LABELS, type PaymentMethod } from '@/components/front-desk/wizard/types'
import { useRecordOrderPayment, type OrderInvoiceSummary } from '@/lib/api/hooks/useOrderPayments'

const inputCls =
  'w-full h-10 px-3 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]'

export function RecordBalancePaymentForm({ orderId, invoice }: { orderId: number; invoice: OrderInvoiceSummary }) {
  const record = useRecordOrderPayment(orderId)
  const [amount, setAmount] = useState('')
  const [method, setMethod] = useState<PaymentMethod | ''>('')
  const [reference, setReference] = useState('')

  const amountNum = parseFloat(amount) || 0
  const exceeds = amountNum > invoice.balance_amount
  const methodMissing = amountNum > 0 && !method
  const showReference = method === 'upi' || method === 'bank_transfer'
  const canSubmit = amountNum > 0 && !exceeds && !!method && !record.isPending

  // Editing any field describes a DIFFERENT payment → mint a fresh idempotency
  // key so the new attempt can't idempotently replay the prior one.
  function onAmountChange(v: string) { setAmount(v); record.resetIdempotencyKey() }
  function onMethodChange(v: PaymentMethod | '') { setMethod(v); record.resetIdempotencyKey() }
  function onReferenceChange(v: string) { setReference(v); record.resetIdempotencyKey() }

  async function submit() {
    if (!canSubmit || !method) return
    try {
      const res = await record.mutateAsync({ amount: amountNum, method, reference: reference || undefined })
      const bal = res.invoice.balance_amount
      toast.success(
        bal <= 0 ? 'Payment recorded. Invoice fully paid.' : `Payment recorded. Balance ${formatINR(bal)} pending.`,
      )
      if (!res.payment.receipt_url) toast.info('Receipt generation pending.')
      setAmount('')
      setMethod('')
      setReference('')
    } catch (err: unknown) {
      const e = err as { message?: string; request_id?: string }
      toast.error(e?.message ?? 'Could not record the payment.', {
        description: e?.request_id ? `request_id: ${e.request_id}` : undefined,
      })
    }
  }

  return (
    <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
      <p className="mb-3 text-sm font-semibold text-[var(--color-text-primary)]">Collect balance payment</p>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <FormField label="Amount (₹)" required error={exceeds ? 'Cannot exceed the outstanding balance.' : undefined}>
          <input
            type="number"
            min={0}
            value={amount}
            onChange={(e) => onAmountChange(e.target.value)}
            className={inputCls}
            placeholder={`Max ${formatINR(invoice.balance_amount)}`}
          />
        </FormField>

        <FormField label="Method" required error={methodMissing ? 'Required.' : undefined}>
          <select value={method} onChange={(e) => onMethodChange(e.target.value as PaymentMethod | '')} className={inputCls}>
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
              value={reference}
              onChange={(e) => onReferenceChange(e.target.value)}
              className={inputCls}
              placeholder="UPI / bank ref"
            />
          </FormField>
        )}
      </div>

      <button
        type="button"
        disabled={!canSubmit}
        onClick={submit}
        className="mt-4 inline-flex items-center justify-center rounded-xl bg-[var(--color-brand)] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        {record.isPending ? 'Recording…' : 'Record Payment'}
      </button>
    </div>
  )
}
