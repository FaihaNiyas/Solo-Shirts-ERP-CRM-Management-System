'use client'

import { useState } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import { X } from 'lucide-react'
import { toast } from 'sonner'
import type { ColumnDef } from '@tanstack/react-table'
import { useInvoices, useCreateInvoice } from '@/lib/api/hooks/useFinance'
import { PageHeader } from '@/components/ui/page-header'
import { DataTable } from '@/components/ui/data-table'
import { StatusBadge } from '@/components/ui/status-badge'
import { CurrencyDisplay } from '@/components/ui/currency-display'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { usePermission } from '@/lib/auth/permissions'
import { format } from 'date-fns'
import type { Invoice } from '@/lib/api/schemas/finance'

function getFiscalYear() {
  const now = new Date()
  const year = now.getMonth() >= 3 ? now.getFullYear() : now.getFullYear() - 1
  return `FY ${year}-${String(year + 1).slice(-2)}`
}

const columns: ColumnDef<Invoice, unknown>[] = [
  {
    accessorKey: 'invoice_number',
    header: 'Invoice #',
    cell: ({ row }) => (
      <span className="font-mono text-xs font-semibold text-[var(--color-text-primary)]">
        {row.original.invoice_number}
      </span>
    ),
  },
  { accessorKey: 'customer_name', header: 'Customer' },
  {
    accessorKey: 'created_at',
    header: 'Date',
    cell: ({ row }) =>
      row.original.created_at
        ? format(new Date(row.original.created_at), 'dd MMM yyyy')
        : '—',
  },
  {
    accessorKey: 'total_amount',
    header: 'Total',
    cell: ({ row }) => <CurrencyDisplay amount={row.original.total_amount ?? 0} />,
  },
  {
    accessorKey: 'paid_amount',
    header: 'Paid',
    cell: ({ row }) => <CurrencyDisplay amount={row.original.paid_amount ?? 0} />,
  },
  {
    accessorKey: 'balance_amount',
    header: 'Balance',
    cell: ({ row }) => (
      <CurrencyDisplay
        amount={row.original.balance_amount ?? 0}
        className={(row.original.balance_amount ?? 0) > 0 ? 'text-red-600 font-semibold' : ''}
      />
    ),
  },
  {
    id: 'status',
    header: 'Status',
    cell: ({ row }) => <StatusBadge status={row.original.status ?? 'draft'} />,
  },
]

export default function InvoicesPage() {
  const { can } = usePermission()
  const router = useRouter()
  const searchParams = useSearchParams()
  const hasAccess = can('finance.view')
  const customerIdParam = searchParams.get('customer_id')
  const [page, setPage] = useState(0)
  const [showCreate, setShowCreate] = useState(false)
  const [orderId, setOrderId] = useState('')
  const { data, isLoading } = useInvoices({
    page: page + 1,
    per_page: 20,
    ...(customerIdParam ? { customer_id: customerIdParam } : {}),
  })
  const createMutation = useCreateInvoice()

  const invoices = Array.isArray(data) ? data : (data?.data ?? [])
  const pageCount = Array.isArray(data) ? 1 : (data ? Math.ceil((data.meta?.total ?? data.total ?? 0) / (data.meta?.per_page ?? data.per_page ?? 20)) : 0)

  if (!hasAccess) {
    return (
      <div className="flex flex-col items-center justify-center h-64 gap-3 text-center">
        <p className="text-lg font-semibold text-red-600">Access Denied</p>
        <p className="text-sm text-muted-foreground">Finance access requires Owner or Accountant role.</p>
      </div>
    )
  }

  async function handleCreate() {
    try {
      const res = await createMutation.mutateAsync({ order_id: parseInt(orderId) })
      setShowCreate(false)
      setOrderId('')
      toast.success('Invoice generated')
      router.push(`/finance/invoices/${res.data.id}`)
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Invoices"
        subtitle={getFiscalYear()}
        actions={
          <button
            onClick={() => setShowCreate(true)}
            className="px-4 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-xl hover:bg-[var(--color-brand-dark)] transition-colors"
          >
            Generate Invoice
          </button>
        }
      />

      {customerIdParam && (
        <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-[var(--color-brand-light)] border border-[var(--color-brand)] text-sm text-[var(--color-brand-dark)]">
          <span className="flex-1">Showing invoices for customer #{customerIdParam}</span>
          <button
            onClick={() => router.replace('/finance/invoices')}
            className="flex items-center gap-1 text-xs hover:underline"
          >
            <X size={12} strokeWidth={2} /> Clear filter
          </button>
        </div>
      )}

      {isLoading ? (
        <TableSkeleton rows={8} cols={7} />
      ) : (
        <DataTable
          data={invoices}
          columns={columns}
          pageCount={pageCount}
          pageIndex={page}
          onPageChange={setPage}
          onRowClick={(row) => router.push(`/finance/invoices/${row.id}`)}
        />
      )}

      <DrawerPanel open={showCreate} onClose={() => { setShowCreate(false); setOrderId('') }} title="Generate Invoice" size="sm">
        <div className="space-y-4 p-4">
          <p className="text-xs text-[var(--color-text-muted)]">
            An invoice will be generated for the selected order.
          </p>
          <FormField label="Order ID" required>
            <input
              type="number"
              value={orderId}
              onChange={(e) => setOrderId(e.target.value)}
              placeholder="Enter order ID"
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </FormField>
          <div className="flex gap-2">
            <button
              onClick={handleCreate}
              disabled={!orderId || createMutation.isPending}
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {createMutation.isPending ? 'Generating…' : 'Generate'}
            </button>
            <button
              onClick={() => { setShowCreate(false); setOrderId('') }}
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
