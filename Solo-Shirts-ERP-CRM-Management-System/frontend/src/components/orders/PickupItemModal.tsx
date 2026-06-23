'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { AlertTriangle, CheckCircle2, Printer } from 'lucide-react'
import { ModalDialog } from '@/components/ui/modal-dialog'
import { FormField } from '@/components/ui/form-field'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { formatINR } from '@/lib/utils'
import {
  useItemPaymentSummary,
  useCreatePickupBatch,
  useCollectPickupPayment,
  useHandoverPickupBatch,
  type PickupBatchSummary,
} from '@/lib/api/hooks/usePickup'

interface Props {
  orderId: number
  item: { id: number; item_code: string }
  open: boolean
  onClose: () => void
}

const METHODS = ['cash', 'upi', 'bank_transfer'] as const

/**
 * Collect-and-handover for ONE ready shirt (Phase 2, pay-now). Walks the counter
 * through: create pickup batch → collect the shirt's balance → hand it over →
 * print the pickup receipt. There is no path here to hand over while unpaid.
 */
export function PickupItemModal({ orderId, item, open, onClose }: Props) {
  const [batch, setBatch] = useState<PickupBatchSummary | null>(null)
  const [method, setMethod] = useState<string>('cash')
  const [reference, setReference] = useState('')

  const { data: summary } = useItemPaymentSummary(orderId, item.id, open && !batch)
  const create = useCreatePickupBatch(orderId)
  const collect = useCollectPickupPayment(orderId, batch?.id ?? 0)
  const handover = useHandoverPickupBatch(orderId, batch?.id ?? 0)

  function reset() {
    setBatch(null)
    setMethod('cash')
    setReference('')
  }

  function close() {
    reset()
    onClose()
  }

  async function startPickup() {
    try {
      const b = await create.mutateAsync({ item_ids: [item.id], pickup_type: 'counter_pickup' })
      setBatch(b)
    } catch (err) {
      toast.error((err as { message?: string })?.message ?? 'Could not start pickup.')
    }
  }

  async function collectPayment() {
    if (!batch) return
    try {
      const b = await collect.mutateAsync({ amount: batch.balance_amount, method, reference: reference || undefined })
      setBatch(b)
      toast.success('Payment collected.')
    } catch (err) {
      toast.error((err as { message?: string })?.message ?? 'Payment failed.')
    }
  }

  async function doHandover() {
    if (!batch) return
    try {
      await handover.mutateAsync()
      setBatch({ ...batch, status: 'handed_over' })
      toast.success('Shirt handed over.')
    } catch (err) {
      toast.error((err as { message?: string })?.message ?? 'Handover failed.')
    }
  }

  async function printReceipt() {
    if (!batch) return
    try {
      const res = await apiGet<{ download_url?: string; url?: string }>(ENDPOINTS.pickupBatchReceipt(orderId, batch.id))
      const url = res.data.download_url ?? res.data.url
      if (url) window.open(url, '_blank')
      else toast.error('Receipt URL unavailable.')
    } catch (err) {
      toast.error((err as { message?: string })?.message ?? 'Could not generate receipt.')
    }
  }

  const dueAmount = batch ? batch.balance_amount : (summary ? summary.item_balance_paise / 100 : 0)
  const orderBalanceAfter = batch
    ? batch.order_balance_amount
    : (summary ? (summary.order_balance_paise - summary.item_balance_paise) / 100 : 0)

  return (
    <ModalDialog open={open} onClose={close} title={`Pickup — ${item.item_code}`}>
      <div className="space-y-4">
        {/* Money summary */}
        <dl className="space-y-1.5 text-sm">
          <Row label="Item total" value={formatINR(batch ? batch.total_amount : (summary ? summary.item_total_paise / 100 : 0))} />
          <Row label="Advance allocated" value={formatINR(summary ? summary.allocated_advance_paise / 100 : 0)} />
          <Row label="Amount due now" value={formatINR(dueAmount)} strong />
          <Row label="Remaining order balance (other items)" value={formatINR(Math.max(0, orderBalanceAfter))} />
        </dl>

        {summary && summary.order_balance_paise - summary.item_balance_paise > 0 && (
          <p className="flex items-center gap-1.5 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
            <AlertTriangle size={13} strokeWidth={1.75} /> Other shirts in this order are still in production / unpaid.
          </p>
        )}

        {/* Step: payment method (when a batch is awaiting payment) */}
        {batch?.status === 'payment_pending' && (
          <div className="grid grid-cols-2 gap-3">
            <FormField label="Method">
              <select
                value={method}
                onChange={(e) => setMethod(e.target.value)}
                className="w-full h-9 px-2 text-sm border border-[var(--color-border)] rounded-lg"
              >
                {METHODS.map((m) => (
                  <option key={m} value={m}>{m.replace('_', ' ')}</option>
                ))}
              </select>
            </FormField>
            <FormField label="Reference (optional)">
              <input
                value={reference}
                onChange={(e) => setReference(e.target.value)}
                className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg"
              />
            </FormField>
          </div>
        )}

        {batch?.status === 'handed_over' && (
          <p className="flex items-center gap-1.5 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">
            <CheckCircle2 size={15} strokeWidth={1.75} /> Handed over. The other items stay in production.
          </p>
        )}

        {/* Actions */}
        <div className="flex flex-wrap justify-end gap-2 border-t border-[var(--color-border)] pt-3">
          <button onClick={close} className="px-4 h-9 text-sm rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
            {batch?.status === 'handed_over' ? 'Close' : 'Keep in Rack / Cancel'}
          </button>

          {!batch && (
            <button
              onClick={startPickup}
              disabled={create.isPending || !summary?.is_ready}
              className="px-4 h-9 text-sm font-medium rounded-lg bg-[var(--color-brand)] text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-50"
            >
              {create.isPending ? 'Starting…' : 'Start Pickup'}
            </button>
          )}

          {batch?.status === 'payment_pending' && (
            <button
              onClick={collectPayment}
              disabled={collect.isPending}
              className="px-4 h-9 text-sm font-medium rounded-lg bg-[var(--color-brand)] text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-50"
            >
              {collect.isPending ? 'Collecting…' : `Collect ${formatINR(dueAmount)}`}
            </button>
          )}

          {batch?.status === 'paid' && (
            <button
              onClick={doHandover}
              disabled={handover.isPending}
              className="px-4 h-9 text-sm font-medium rounded-lg bg-[var(--color-success)] text-white hover:opacity-90 disabled:opacity-50"
            >
              {handover.isPending ? 'Handing over…' : 'Handover'}
            </button>
          )}

          {batch?.status === 'handed_over' && (
            <button
              onClick={printReceipt}
              className="inline-flex items-center gap-1.5 px-4 h-9 text-sm font-medium rounded-lg border border-[var(--color-brand)] text-[var(--color-brand-dark)] hover:bg-[var(--color-brand-light)]"
            >
              <Printer size={14} strokeWidth={1.75} /> Print Pickup Receipt
            </button>
          )}
        </div>
      </div>
    </ModalDialog>
  )
}

function Row({ label, value, strong }: { label: string; value: string; strong?: boolean }) {
  return (
    <div className="flex items-center justify-between gap-4">
      <dt className="text-[var(--color-text-muted)]">{label}</dt>
      <dd className={strong ? 'font-semibold text-[var(--color-text-primary)]' : 'text-[var(--color-text-primary)]'}>{value}</dd>
    </div>
  )
}
