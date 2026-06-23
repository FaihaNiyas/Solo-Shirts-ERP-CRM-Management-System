'use client'

import { use, useState } from 'react'
import { toast } from 'sonner'
import { Download, Info } from 'lucide-react'
import {
  useInvoice,
  usePaymentsByInvoice,
  useRecordPayment,
  useIssueCreditNote,
} from '@/lib/api/hooks/useFinance'
import { PageHeader } from '@/components/ui/page-header'
import { StatusBadge } from '@/components/ui/status-badge'
import { CurrencyDisplay } from '@/components/ui/currency-display'
import { InfoGrid } from '@/components/ui/info-grid'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { format } from 'date-fns'

const PAYMENT_METHODS = ['Cash', 'UPI', 'Card', 'Bank Transfer', 'Cheque']

export default function InvoiceDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const invoiceId = parseInt(id)

  const { data: invoice, isLoading } = useInvoice(invoiceId)
  const { data: payments = [] } = usePaymentsByInvoice(invoiceId)
  const recordPayment = useRecordPayment()
  const issueCreditNote = useIssueCreditNote(invoiceId)

  const [showPayment, setShowPayment] = useState(false)
  const [showCreditNote, setShowCreditNote] = useState(false)

  const [paymentForm, setPaymentForm] = useState({
    amount: '',
    method: 'Cash',
    reference: '',
    notes: '',
  })

  const [creditForm, setCreditForm] = useState({ reason: '', amount: '' })

  async function handleRecordPayment() {
    try {
      await recordPayment.mutateAsync({
        invoice_id: invoiceId,
        amount: parseFloat(paymentForm.amount),
        method: paymentForm.method,
        reference: paymentForm.reference || undefined,
        notes: paymentForm.notes || undefined,
      })
      setShowPayment(false)
      setPaymentForm({ amount: '', method: 'Cash', reference: '', notes: '' })
      toast.success('Payment recorded')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  async function handleCreditNote() {
    try {
      await issueCreditNote.mutateAsync({
        reason: creditForm.reason,
        amount: parseFloat(creditForm.amount),
      })
      setShowCreditNote(false)
      setCreditForm({ reason: '', amount: '' })
      toast.success('Credit note issued')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  if (isLoading) return <TableSkeleton rows={4} cols={4} />
  if (!invoice) return <p className="text-sm text-[var(--color-text-muted)]">Invoice not found</p>

  return (
    <div className="space-y-6">
      <PageHeader
        title={invoice.invoice_number ?? `Invoice #${invoiceId}`}
        actions={
          <div className="flex gap-2">
            <button
              onClick={() => window.open(ENDPOINTS.invoicePdf(invoiceId), '_blank')}
              className="flex items-center gap-2 px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              <Download size={15} strokeWidth={1.75} /> PDF
            </button>
            <button
              onClick={() => setShowCreditNote(true)}
              className="px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Issue Credit Note
            </button>
          </div>
        }
      />

      {/* Read-only banner */}
      <div className="flex items-center gap-2 px-4 py-3 rounded-xl bg-blue-50 border border-blue-200 text-sm text-blue-700">
        <Info size={15} strokeWidth={1.75} />
        This invoice cannot be edited. To correct an error, issue a credit note.
      </div>

      {/* THREE key metrics */}
      <div className="grid grid-cols-3 gap-4">
        <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <p className="text-xs text-[var(--color-text-muted)] mb-1">Invoice Total</p>
          <CurrencyDisplay amount={invoice.total_amount ?? 0} className="text-2xl font-semibold" />
        </div>
        <div className="rounded-xl border border-green-200 bg-green-50 p-4">
          <p className="text-xs text-green-600 mb-1">Amount Paid</p>
          <CurrencyDisplay amount={invoice.paid_amount ?? 0} className="text-2xl font-semibold text-green-700" />
        </div>
        <div className="rounded-xl border border-red-200 bg-red-50 p-4">
          <p className="text-xs text-red-600 mb-1">Balance Due</p>
          <CurrencyDisplay amount={invoice.balance_amount ?? 0} className="text-2xl font-semibold text-red-600" />
        </div>
      </div>

      <InfoGrid
        items={[
          { label: 'Invoice #', value: <span className="font-mono">{invoice.invoice_number}</span> },
          { label: 'Customer', value: invoice.customer_name ?? '—' },
          { label: 'Order', value: invoice.order_number ?? '—' },
          { label: 'GST Type', value: invoice.gst_type === 'igst' ? 'IGST' : 'CGST + SGST' },
          { label: 'Status', value: <StatusBadge status={invoice.status ?? 'draft'} /> },
          {
            label: 'Date',
            value: invoice.created_at
              ? format(new Date(invoice.created_at), 'dd MMM yyyy')
              : '—',
          },
        ]}
      />

      {/* Payments — read-only */}
      <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
        <div className="flex items-center justify-between px-4 py-3 bg-[var(--color-surface-alt)] border-b border-[var(--color-border)]">
          <p className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">
            Payments
          </p>
          <button
            onClick={() => setShowPayment(true)}
            disabled={invoice.status === 'paid' || invoice.status === 'cancelled'}
            className="px-3 py-1.5 text-xs font-medium bg-[var(--color-brand)] text-white rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-40 transition-colors"
          >
            Record Payment
          </button>
        </div>
        <div className="divide-y divide-[var(--color-border)] bg-white">
          {payments.map((p) => (
            <div key={p.id} className="flex items-center gap-4 px-4 py-3">
              <div className="flex-1">
                <div className="flex items-center gap-2">
                  <CurrencyDisplay amount={p.amount ?? 0} className="text-sm font-semibold" />
                  <span className="px-1.5 py-0.5 text-xs bg-[var(--color-surface-alt)] text-[var(--color-text-muted)] rounded">
                    {p.method}
                  </span>
                </div>
                {p.reference && (
                  <p className="text-xs text-[var(--color-text-muted)] font-mono">{p.reference}</p>
                )}
              </div>
              <span className="text-xs text-[var(--color-text-muted)]">
                {p.created_at ? format(new Date(p.created_at), 'dd MMM yyyy') : '—'}
              </span>
            </div>
          ))}
          {payments.length === 0 && (
            <div className="py-6 text-center text-sm text-[var(--color-text-muted)]">
              No payments recorded
            </div>
          )}
        </div>
      </div>

      {/* Record payment drawer */}
      <DrawerPanel open={showPayment} onClose={() => setShowPayment(false)} title="Record Payment" size="md">
        <div className="space-y-4 p-4">
          <div className="flex items-center gap-2 p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-700">
            This payment cannot be undone. Corrections require a credit note.
          </div>
          <FormField label="Amount" required>
            <input
              type="number"
              step="0.01"
              value={paymentForm.amount}
              onChange={(e) => setPaymentForm((p) => ({ ...p, amount: e.target.value }))}
              className="w-full h-9 px-3 text-sm font-mono border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              placeholder="0.00"
            />
          </FormField>
          <FormField label="Method">
            <select
              value={paymentForm.method}
              onChange={(e) => setPaymentForm((p) => ({ ...p, method: e.target.value }))}
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] bg-white"
            >
              {PAYMENT_METHODS.map((m) => <option key={m}>{m}</option>)}
            </select>
          </FormField>
          <FormField label="Reference">
            <input
              value={paymentForm.reference}
              onChange={(e) => setPaymentForm((p) => ({ ...p, reference: e.target.value }))}
              placeholder="Transaction ID or cheque no."
              className="w-full h-9 px-3 text-sm font-mono border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </FormField>
          <div className="flex gap-2">
            <button
              onClick={handleRecordPayment}
              disabled={!paymentForm.amount || recordPayment.isPending}
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {recordPayment.isPending ? 'Recording…' : 'Record Payment'}
            </button>
            <button
              onClick={() => setShowPayment(false)}
              className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </DrawerPanel>

      {/* Credit note drawer */}
      <DrawerPanel open={showCreditNote} onClose={() => setShowCreditNote(false)} title="Issue Credit Note" size="md">
        <div className="space-y-4 p-4">
          <FormField label="Reason" required>
            <textarea
              value={creditForm.reason}
              onChange={(e) => setCreditForm((p) => ({ ...p, reason: e.target.value }))}
              rows={3}
              className="w-full px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] resize-none"
              placeholder="Reason for credit note"
            />
          </FormField>
          <FormField label="Amount" required>
            <input
              type="number"
              step="0.01"
              value={creditForm.amount}
              onChange={(e) => setCreditForm((p) => ({ ...p, amount: e.target.value }))}
              className="w-full h-9 px-3 text-sm font-mono border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              placeholder="0.00"
            />
          </FormField>
          <div className="flex gap-2">
            <button
              onClick={handleCreditNote}
              disabled={!creditForm.reason || !creditForm.amount || issueCreditNote.isPending}
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {issueCreditNote.isPending ? 'Issuing…' : 'Issue Credit Note'}
            </button>
            <button
              onClick={() => setShowCreditNote(false)}
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
