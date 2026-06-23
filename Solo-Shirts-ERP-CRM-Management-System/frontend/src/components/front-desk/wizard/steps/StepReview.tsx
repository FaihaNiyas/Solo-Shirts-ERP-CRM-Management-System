'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { AlertTriangle, CheckCircle2, ArrowRight, ReceiptText } from 'lucide-react'
import { apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'
import { SectionCard } from '@/components/ui/section-card'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { formatINR } from '@/lib/utils'
import { DELIVERY_MODE_LABELS, PAYMENT_METHOD_LABELS, SOURCE_LABELS } from '../types'
import { useWizard } from '../WizardContext'
import { pdfReady, confirmBlockers, confirmWarnings } from '../validation'

interface ConfirmSummary {
  order: { id: number }
  invoice: {
    id: number
    invoice_number: string
    total_amount: number
    paid_amount: number
    balance_amount: number
    status: string
  } | null
  payment: { id: number; amount: number; method: string; status: string } | null
}

export function StepReview() {
  const router = useRouter()
  const qc = useQueryClient()
  const { orderId, customer, memberLabel, meta, subOrders, payment, grandTotal, balance, finalizeConfirmed } = useWizard()

  const [dialogOpen, setDialogOpen] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [result, setResult] = useState<ConfirmSummary | null>(null)

  const blockers = confirmBlockers({ customer, meta, subOrders })
  const warnings = confirmWarnings(subOrders)
  const canConfirm = blockers.length === 0 && orderId !== null

  async function handleConfirm() {
    if (!orderId) return
    setSubmitting(true)
    try {
      const res = await apiMutate<ConfirmSummary>('post', ENDPOINTS.confirmOrder(orderId), {
        pricing: {
          lines: subOrders.map((s) => ({
            order_item_id: s.itemId,
            base_price: s.basePrice,
            // Cap the discount at the line's base price so it matches the live
            // total shown here (the server rejects a discount above the price).
            discount_amount: Math.min(s.discountAmount, s.basePrice),
            gst_rate: s.gstRate,
          })),
        },
        payment:
          payment.advancePaid > 0
            ? {
                advance_amount: payment.advancePaid,
                method: payment.method,
                reference: payment.reference || undefined,
              }
            : { advance_amount: 0 },
      })
      setResult(res.data)
      setDialogOpen(false)
      finalizeConfirmed() // clear the saved draft but keep the success screen visible
      // The wizard confirms via a raw apiMutate (not a useMutation hook), so it
      // must invalidate the same caches the order hooks do — otherwise the new
      // order stays hidden behind the 3-min staleTime on the list/dashboard.
      qc.invalidateQueries({ queryKey: queryKeys.orders() })
      qc.invalidateQueries({ queryKey: queryKeys.order(orderId) })
      qc.invalidateQueries({ queryKey: queryKeys.frontDeskDashboard() })
      qc.invalidateQueries({ queryKey: ['front-desk-drafts'] })
      if (customer?.id) qc.invalidateQueries({ queryKey: queryKeys.customer(customer.id) })
      toast.success('Order confirmed and sent to production.')
    } catch (err: unknown) {
      const e = err as { message?: string; request_id?: string; errors?: Record<string, string[]> }
      // Surface the specific field error (e.g. a bad discount/GST) instead of the
      // generic "The given data was invalid".
      const fieldError = e?.errors ? Object.values(e.errors)[0]?.[0] : undefined
      toast.error(fieldError ?? e?.message ?? 'Could not confirm the order.', {
        description: e?.request_id ? `request_id: ${e.request_id}` : undefined,
      })
    } finally {
      setSubmitting(false)
    }
  }

  // --- Success screen ---------------------------------------------------------
  if (result) {
    const inv = result.invoice
    const hadAdvance = (result.payment?.amount ?? 0) > 0
    const balanceLine = inv ? formatINR(inv.balance_amount) : '—'

    return (
      <div className="mx-auto max-w-lg">
        <div className="rounded-2xl border border-green-200 bg-green-50 px-6 py-7 text-center">
          <CheckCircle2 size={34} strokeWidth={1.75} className="mx-auto text-[var(--color-success)] mb-2" />
          <p className="text-base font-semibold text-[var(--color-text-primary)]">Order confirmed and sent to production.</p>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            {hadAdvance
              ? `Advance payment recorded. Balance ${balanceLine} pending.`
              : `Confirmed without advance. Balance ${balanceLine} pending.`}
          </p>
        </div>

        {inv && (
          <div className="mt-4 rounded-xl border border-[var(--color-border)] bg-white p-4">
            <div className="flex items-center gap-2 mb-3">
              <ReceiptText size={16} strokeWidth={1.75} className="text-[var(--color-brand)]" />
              <span className="ss-mono text-sm font-semibold text-[var(--color-text-primary)]">{inv.invoice_number}</span>
            </div>
            <dl className="space-y-1.5 text-sm">
              <Row label="Invoice total" value={formatINR(inv.total_amount)} />
              <Row label="Advance paid" value={formatINR(inv.paid_amount)} />
              <Row label="Balance pending" value={formatINR(inv.balance_amount)} />
              {result.payment && <Row label="Method" value={PAYMENT_METHOD_LABELS[result.payment.method as keyof typeof PAYMENT_METHOD_LABELS] ?? result.payment.method} />}
            </dl>
          </div>
        )}

        <button
          type="button"
          onClick={() => router.push(`/orders/${result.order.id}`)}
          className="mt-4 w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-[var(--color-brand)] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] transition-colors"
        >
          View order <ArrowRight size={15} strokeWidth={2} />
        </button>
      </div>
    )
  }

  // --- Review form ------------------------------------------------------------
  return (
    <div className="space-y-4">
      {canConfirm ? (
        <div className="flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
          <CheckCircle2 size={16} strokeWidth={1.75} />
          All required details are complete. Payment will be recorded on confirm.
        </div>
      ) : (
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
          <div className="flex items-center gap-2 text-sm font-medium text-amber-800 mb-1.5">
            <AlertTriangle size={16} strokeWidth={1.75} />
            Resolve these before confirming:
          </div>
          <ul className="list-disc pl-8 space-y-0.5 text-xs text-amber-700">
            {blockers.map((it, i) => (
              <li key={i}>{it}</li>
            ))}
          </ul>
        </div>
      )}

      {warnings.length > 0 && (
        <div className="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-xs text-orange-700">
          {warnings.map((w, i) => (
            <p key={i}>⚠ {w}</p>
          ))}
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <SectionCard title="Customer & order">
          <dl className="space-y-1.5 text-sm">
            <Row label="Customer" value={customer?.name ?? '—'} />
            <Row label="Order for" value={memberLabel || '—'} />
            <Row label="Source" value={SOURCE_LABELS[meta.source]} />
            <Row label="Delivery mode" value={DELIVERY_MODE_LABELS[meta.deliveryMode]} />
            <Row label="Delivery date" value={meta.deliveryDate || '—'} />
            {meta.notes && <Row label="Notes" value={meta.notes} />}
          </dl>
        </SectionCard>

        <SectionCard title="Payment (recorded on confirm)">
          <dl className="space-y-1.5 text-sm">
            <Row label="Grand total" value={formatINR(grandTotal)} />
            <Row label="Advance" value={formatINR(payment.advancePaid)} />
            <Row label="Balance" value={formatINR(balance)} />
            <Row label="Method" value={payment.method ? PAYMENT_METHOD_LABELS[payment.method] : '—'} />
            {payment.reference && <Row label="Reference" value={payment.reference} />}
          </dl>
          {payment.advancePaid === 0 && (
            <p className="mt-2 flex items-center gap-1.5 text-xs text-[var(--color-warning)]">
              <AlertTriangle size={13} strokeWidth={1.75} /> No advance — full balance will be outstanding.
            </p>
          )}
        </SectionCard>
      </div>

      <SectionCard title={`Shirts (${subOrders.length})`}>
        <div className="space-y-2">
          {subOrders.map((s, i) => (
            <div
              key={s.tempId}
              className="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
            >
              <span className="ss-mono font-semibold text-[var(--color-text-primary)]">
                Sub-order {String(i + 1).padStart(2, '0')}
              </span>
              <span className="text-[var(--color-text-muted)]">·</span>
              <span className="text-[var(--color-text-secondary)]">{s.fabricLabel ?? 'No fabric'}</span>
              <div className="ml-auto flex items-center gap-1.5">
                <Tag ok={pdfReady(s)} text={pdfReady(s) ? 'PDF ready' : 'PDF missing'} />
                <Tag ok={s.printStatus === 'printed'} text={s.printStatus === 'printed' ? 'Printed' : 'Not printed'} />
              </div>
            </div>
          ))}
        </div>
      </SectionCard>

      <div className="flex justify-end">
        <button
          type="button"
          disabled={!canConfirm || submitting}
          onClick={() => setDialogOpen(true)}
          className="inline-flex items-center justify-center rounded-xl bg-[var(--color-brand)] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
          Confirm Order
        </button>
      </div>

      <ConfirmDialog
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
        onConfirm={handleConfirm}
        title="Confirm order?"
        description={
          (warnings.length > 0 ? `${warnings.join(' ')} ` : '') +
          (payment.advancePaid > 0
            ? `Record an advance of ${formatINR(payment.advancePaid)} and send the order to production?`
            : `Confirm without an advance and send the order to production?`)
        }
        variant={warnings.length > 0 ? 'warning' : 'info'}
        confirmLabel="Confirm Order"
        loading={submitting}
      />
    </div>
  )
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-start justify-between gap-4">
      <dt className="text-[var(--color-text-muted)] shrink-0">{label}</dt>
      <dd className="text-right font-medium text-[var(--color-text-primary)] min-w-0 break-words">{value}</dd>
    </div>
  )
}

function Tag({ ok, text }: { ok: boolean; text: string }) {
  return (
    <span
      className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${
        ok ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-800'
      }`}
    >
      {text}
    </span>
  )
}
