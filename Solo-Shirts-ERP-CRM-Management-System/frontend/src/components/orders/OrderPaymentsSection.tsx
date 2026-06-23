'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { Info, Download } from 'lucide-react'
import { cn, formatINR } from '@/lib/utils'
import { usePermission } from '@/lib/auth/permissions'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useOrderPayments } from '@/lib/api/hooks/useOrderPayments'
import { RecordBalancePaymentForm } from './RecordBalancePaymentForm'
import { PaymentHistoryPanel } from './PaymentHistoryPanel'

const INVOICE_STATUS_STYLE: Record<string, string> = {
  paid: 'bg-green-100 text-green-700',
  partially_paid: 'bg-amber-100 text-amber-800',
  issued: 'bg-blue-50 text-blue-700',
  credited: 'bg-gray-100 text-gray-600',
}

export function OrderPaymentsSection({ orderId }: { orderId: number }) {
  const { data, isLoading } = useOrderPayments(orderId)
  const { can } = usePermission()
  const canCollect = can('orders.collect_payment')
  const canDownloadInvoice = can('finance.invoice.download')
  const [downloading, setDownloading] = useState(false)

  async function downloadInvoice(invoiceId: number) {
    setDownloading(true)
    try {
      const res = await apiGet<{ download_url?: string }>(ENDPOINTS.invoicePdf(invoiceId))
      if (res.data.download_url) window.open(res.data.download_url, '_blank', 'noopener')
      else toast.error('Invoice URL unavailable.')
    } catch (err) {
      toast.error((err as { message?: string })?.message ?? 'Could not generate the invoice PDF.')
    } finally {
      setDownloading(false)
    }
  }

  if (isLoading || !data) {
    return (
      <Shell>
        <p className="py-4 text-center text-sm text-[var(--color-text-muted)]">Loading payments…</p>
      </Shell>
    )
  }

  const { invoice, lifecycle_status, payments } = data
  const isIntake = lifecycle_status === 'intake_preparation'
  const isCancelled = lifecycle_status === 'cancelled'
  const fullyPaid = invoice !== null && invoice.balance_amount <= 0

  return (
    <Shell>
      {/* Lifecycle / invoice notices */}
      {isIntake && <Notice>Payment can be collected after the order is confirmed.</Notice>}
      {isCancelled && <Notice>This order is cancelled — no payments can be collected.</Notice>}
      {!isIntake && !isCancelled && invoice === null && <Notice>Invoice not created yet.</Notice>}

      {invoice && (
        <>
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <Metric label="Invoice" value={invoice.invoice_number} mono />
            <Metric label="Total" value={formatINR(invoice.total_amount)} />
            <Metric label="Paid" value={formatINR(invoice.paid_amount)} tone="success" />
            <Metric label="Balance" value={formatINR(invoice.balance_amount)} tone={invoice.balance_amount > 0 ? 'warning' : 'muted'} />
          </div>

          <div className="mt-2 flex items-center gap-3">
            <span
              className={cn(
                'inline-block rounded-full px-2.5 py-0.5 text-xs font-medium',
                INVOICE_STATUS_STYLE[invoice.status] ?? 'bg-gray-100 text-gray-600',
              )}
            >
              {fullyPaid ? 'Paid' : invoice.status.replace('_', ' ')}
            </span>
            {canDownloadInvoice && (
              <button
                onClick={() => downloadInvoice(invoice.id)}
                disabled={downloading}
                className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 py-1 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-50"
              >
                <Download size={13} strokeWidth={1.75} /> {downloading ? 'Preparing…' : 'Download Invoice'}
              </button>
            )}
          </div>

          {canCollect && !fullyPaid && <RecordBalancePaymentForm orderId={orderId} invoice={invoice} />}
          {fullyPaid && (
            <Notice tone="success">Invoice fully paid — nothing outstanding.</Notice>
          )}
        </>
      )}

      <div>
        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">Payment history</p>
        <PaymentHistoryPanel payments={payments} />
      </div>
    </Shell>
  )
}

function Shell({ children }: { children: React.ReactNode }) {
  return (
    <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-4">
      <h2 className="text-base font-semibold text-[var(--color-text-primary)]">Payments</h2>
      {children}
    </section>
  )
}

function Notice({ children, tone = 'info' }: { children: React.ReactNode; tone?: 'info' | 'success' }) {
  return (
    <div
      className={cn(
        'flex items-start gap-2 rounded-lg px-3 py-2.5 text-xs',
        tone === 'success' ? 'bg-green-50 border border-green-100 text-green-700' : 'bg-blue-50 border border-blue-100 text-blue-700',
      )}
    >
      <Info size={14} strokeWidth={1.75} className="mt-0.5 shrink-0" />
      <span>{children}</span>
    </div>
  )
}

function Metric({
  label,
  value,
  tone = 'default',
  mono = false,
}: {
  label: string
  value: string
  tone?: 'default' | 'success' | 'warning' | 'muted'
  mono?: boolean
}) {
  return (
    <div className="rounded-lg border border-[var(--color-border)] px-3 py-2">
      <p className="text-[11px] uppercase tracking-wide text-[var(--color-text-muted)]">{label}</p>
      <p
        className={cn(
          'text-sm font-semibold',
          mono && 'ss-mono',
          tone === 'success' && 'text-[var(--color-success)]',
          tone === 'warning' && 'text-[var(--color-warning)]',
          tone === 'muted' && 'text-[var(--color-text-muted)]',
          tone === 'default' && 'text-[var(--color-text-primary)]',
        )}
      >
        {value}
      </p>
    </div>
  )
}
