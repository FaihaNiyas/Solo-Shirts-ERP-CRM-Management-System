'use client'

import { useMemo, useState } from 'react'
import { toast } from 'sonner'
import type { ColumnDef } from '@tanstack/react-table'
import {
  useDeliveries,
  useDispatchDelivery,
  useConfirmDelivery,
  useAttemptDelivery,
} from '@/lib/api/hooks/useDeliveries'
import { PageHeader } from '@/components/ui/page-header'
import { DataTable } from '@/components/ui/data-table'
import { StatusBadge } from '@/components/ui/status-badge'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { ModalDialog } from '@/components/ui/modal-dialog'
import { FormField } from '@/components/ui/form-field'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { format } from 'date-fns'
import { formatINR } from '@/lib/utils'
import type { Delivery } from '@/lib/api/schemas/delivery'

export default function DeliveriesPage() {
  const [page, setPage] = useState(0)
  const { data, isLoading } = useDeliveries({ page: page + 1, per_page: 20 })

  const [dispatchTarget, setDispatchTarget] = useState<number | null>(null)
  const [otpTarget, setOtpTarget] = useState<number | null>(null)
  const [failTarget, setFailTarget] = useState<number | null>(null)
  const [otpValue, setOtpValue] = useState('')

  const dispatchMutation = useDispatchDelivery(dispatchTarget ?? 0)
  const confirmMutation = useConfirmDelivery(otpTarget ?? 0)
  const attemptMutation = useAttemptDelivery(failTarget ?? 0)

  const deliveries = data?.data ?? []
  const pageCount = data ? Math.ceil((data.meta?.total ?? data.total ?? 0) / (data.meta?.per_page ?? data.per_page ?? 20)) : 0

  const columns: ColumnDef<Delivery, unknown>[] = useMemo(() => [
    {
      accessorKey: 'delivery_number',
      header: 'Delivery #',
      cell: ({ row }) => (
        <span className="font-mono text-xs font-semibold">{row.original.delivery_number}</span>
      ),
    },
    { accessorKey: 'order_number', header: 'Order #' },
    { accessorKey: 'customer_name', header: 'Customer' },
    { id: 'status', header: 'Status', cell: ({ row }) => <StatusBadge status={row.original.status ?? 'scheduled'} /> },
    {
      id: 'order_progress',
      header: 'Order',
      cell: ({ row }) =>
        row.original.order_progress ? (
          <span className="text-xs text-[var(--color-text-secondary)]">{row.original.order_progress.summary_label}</span>
        ) : (
          <span className="text-xs text-[var(--color-text-muted)]">—</span>
        ),
    },
    {
      id: 'balance',
      header: 'Balance',
      cell: ({ row }) =>
        row.original.balance_pending ? (
          <span className="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
            {formatINR(row.original.balance_amount ?? 0)} pending
          </span>
        ) : (
          <span className="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
            Fully paid
          </span>
        ),
    },
    {
      accessorKey: 'created_at',
      header: 'Created',
      cell: ({ row }) =>
        row.original.created_at
          ? format(new Date(row.original.created_at), 'dd MMM yyyy')
          : '—',
    },
    {
      id: 'actions',
      header: 'Actions',
      cell: ({ row }) => {
        const s = row.original.status
        const balancePending = row.original.balance_pending === true
        // Server-side gate is authoritative; the UI disables proactively and
        // explains why, so staff collect the balance before dispatch/confirm.
        const blockTitle = balancePending ? 'Balance pending — collect payment before delivery.' : undefined
        return (
          <div className="flex gap-1.5">
            {s === 'scheduled' && (
              <button
                onClick={() => setDispatchTarget(row.original.id)}
                disabled={balancePending}
                title={blockTitle}
                className="px-2 py-1 text-xs border border-[var(--color-brand)] text-[var(--color-brand)] rounded hover:bg-[var(--color-brand-light)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              >
                Dispatch
              </button>
            )}
            {s === 'dispatched' && (
              <>
                <button
                  onClick={() => { setOtpTarget(row.original.id); setOtpValue('') }}
                  disabled={balancePending}
                  title={blockTitle}
                  className="px-2 py-1 text-xs border border-green-500 text-green-600 rounded hover:bg-green-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                  Confirm OTP
                </button>
                <button
                  onClick={() => setFailTarget(row.original.id)}
                  className="px-2 py-1 text-xs border border-[var(--color-border)] text-[var(--color-text-muted)] rounded hover:bg-[var(--color-surface-alt)] transition-colors"
                >
                  Failed
                </button>
              </>
            )}
          </div>
        )
      },
    },
  ], [])

  return (
    <div className="space-y-6">
      <PageHeader title="Deliveries" />

      {isLoading ? (
        <TableSkeleton rows={6} cols={6} />
      ) : (
        <DataTable
          data={deliveries}
          columns={columns}
          pageCount={pageCount}
          pageIndex={page}
          onPageChange={setPage}
        />
      )}

      <ConfirmDialog
        open={dispatchTarget !== null}
        onClose={() => setDispatchTarget(null)}
        onConfirm={async () => {
          try {
            await dispatchMutation.mutateAsync({})
            toast.success('Dispatched — OTP sent to customer')
            setDispatchTarget(null)
          } catch (err: unknown) {
            toast.error((err as { message?: string })?.message ?? 'Failed')
          }
        }}
        title="Dispatch Delivery"
        description="An OTP will be sent to the customer's phone."
        variant="info"
        loading={dispatchMutation.isPending}
      />

      <ModalDialog
        open={otpTarget !== null}
        onClose={() => { setOtpTarget(null); setOtpValue('') }}
        title="Confirm OTP"
        footer={
          <div className="flex gap-2 justify-end">
            <button
              onClick={() => { setOtpTarget(null); setOtpValue('') }}
              className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Cancel
            </button>
            <button
              onClick={async () => {
                if (!otpValue.trim()) return
                try {
                  await confirmMutation.mutateAsync({ otp: otpValue })
                  toast.success('Delivery confirmed')
                  setOtpTarget(null)
                  setOtpValue('')
                } catch (err: unknown) {
                  const e = err as { message?: string; status?: number }
                  if (e?.status === 423) {
                    toast.error('OTP locked after 5 attempts. Re-dispatch required.')
                  } else {
                    toast.error(e?.message ?? 'Invalid OTP')
                  }
                }
              }}
              disabled={!otpValue.trim() || confirmMutation.isPending}
              className="px-6 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {confirmMutation.isPending ? 'Confirming…' : 'Confirm'}
            </button>
          </div>
        }
      >
        <FormField label="OTP from customer">
          <input
            type="text"
            value={otpValue}
            onChange={(e) => setOtpValue(e.target.value)}
            maxLength={6}
            placeholder="6-digit OTP"
            className="w-full h-9 px-3 text-sm font-mono text-center tracking-widest border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
          />
        </FormField>
      </ModalDialog>

      <ConfirmDialog
        open={failTarget !== null}
        onClose={() => setFailTarget(null)}
        onConfirm={async (reason) => {
          try {
            await attemptMutation.mutateAsync({ reason: reason ?? 'Not reachable' })
            toast.success('Failed attempt recorded')
            setFailTarget(null)
          } catch (err: unknown) {
            toast.error((err as { message?: string })?.message ?? 'Failed')
          }
        }}
        title="Log Failed Delivery Attempt"
        description="Provide a reason for the failed delivery attempt."
        variant="warning"
        requireReason
        loading={attemptMutation.isPending}
      />
    </div>
  )
}
