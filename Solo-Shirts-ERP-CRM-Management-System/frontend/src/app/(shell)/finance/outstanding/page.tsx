'use client'

import { useRouter } from 'next/navigation'
import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'
import type { ColumnDef } from '@tanstack/react-table'
import { PageHeader } from '@/components/ui/page-header'
import { DataTable } from '@/components/ui/data-table'
import { CurrencyDisplay } from '@/components/ui/currency-display'
import { usePermission } from '@/lib/auth/permissions'
import { TableSkeleton } from '@/components/ui/loading-skeleton'

interface OutstandingRow {
  id: number
  customer_id?: number
  customer_name?: string
  invoice_count?: number
  total_outstanding_paise?: number
  oldest_invoice_date?: string
}

const columns: ColumnDef<OutstandingRow, unknown>[] = [
  { accessorKey: 'customer_name', header: 'Customer' },
  {
    accessorKey: 'invoice_count',
    header: 'Open Invoices',
    cell: ({ row }) => <span className="tabular-nums">{row.original.invoice_count ?? 0}</span>,
  },
  {
    accessorKey: 'total_outstanding_paise',
    header: 'Outstanding',
    cell: ({ row }) => (
      <CurrencyDisplay
        amount={(row.original.total_outstanding_paise ?? 0) / 100}
        className="font-semibold text-red-600"
      />
    ),
  },
  {
    accessorKey: 'oldest_invoice_date',
    header: 'Oldest Invoice',
    cell: ({ row }) =>
      row.original.oldest_invoice_date
        ? new Date(row.original.oldest_invoice_date).toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
          })
        : '—',
  },
]

export default function OutstandingPage() {
  const { can } = usePermission()
  const router = useRouter()
  const hasAccess = can('finance.view')

  const { data: rows = [], isLoading } = useQuery<OutstandingRow[]>({
    queryKey: queryKeys.financeOutstanding(),
    queryFn: () => apiGet<OutstandingRow[]>(ENDPOINTS.financeOutstanding).then((r) => r.data),
    enabled: hasAccess,
  })

  const grandTotalPaise = rows.reduce((s, r) => s + (r.total_outstanding_paise ?? 0), 0)

  if (!hasAccess) {
    return (
      <div className="flex flex-col items-center justify-center h-64 gap-3 text-center">
        <p className="text-lg font-semibold text-red-600">Access Denied</p>
        <p className="text-sm text-muted-foreground">Finance access requires Owner or Accountant role.</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Outstanding Balances" />

      {grandTotalPaise > 0 && (
        <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 flex items-center justify-between">
          <p className="text-sm font-medium text-red-700">Total Outstanding</p>
          <CurrencyDisplay amount={grandTotalPaise / 100} className="text-xl font-bold text-red-700" />
        </div>
      )}

      {isLoading ? (
        <TableSkeleton rows={6} cols={4} />
      ) : (
        <DataTable
          data={rows}
          columns={columns}
          pageCount={1}
          pageIndex={0}
          onPageChange={() => {}}
          onRowClick={(row) => router.push(`/finance/invoices?customer_id=${row.customer_id ?? row.id}`)}
        />
      )}
    </div>
  )
}
