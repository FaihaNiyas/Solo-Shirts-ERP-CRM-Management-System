'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import type { ColumnDef } from '@tanstack/react-table'
import { Plus } from 'lucide-react'
import Link from 'next/link'
import { useOrders } from '@/lib/api/hooks/useOrders'
import { PageHeader } from '@/components/ui/page-header'
import { SearchInput } from '@/components/ui/search-input'
import { DataTable } from '@/components/ui/data-table'
import { StatusBadge } from '@/components/ui/status-badge'
import { CurrencyDisplay } from '@/components/ui/currency-display'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { format } from 'date-fns'
import type { Order } from '@/lib/api/schemas/orders'
import { orderDisplayStatus } from '@/lib/orders/displayStatus'


const columns: ColumnDef<Order, unknown>[] = [
  {
    accessorKey: 'order_code',
    header: 'Order #',
    cell: ({ row }) => (
      <span className="font-mono text-xs text-[var(--color-brand)] font-semibold">
        {row.original.order_code}
      </span>
    ),
  },
  {
    accessorKey: 'customer_name',
    header: 'Customer',
    cell: ({ row }) => <span className="text-sm">{row.original.customer_name ?? '—'}</span>,
  },
  {
    id: 'item_count',
    header: 'Items',
    cell: ({ row }) => (
      <span className="text-sm text-[var(--color-text-muted)]">
        {row.original.item_count ?? row.original.items?.length ?? 0}
      </span>
    ),
  },
  {
    id: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const s = orderDisplayStatus(row.original)
      return <StatusBadge status={s.value} label={s.label} />
    },
  },
  {
    accessorKey: 'expected_delivery_date',
    header: 'Delivery',
    cell: ({ row }) => (
      <span className="text-sm text-[var(--color-text-muted)]">
        {row.original.expected_delivery_date
          ? format(new Date(row.original.expected_delivery_date), 'dd MMM yyyy')
          : '—'}
      </span>
    ),
  },
  {
    accessorKey: 'total_amount',
    header: 'Total',
    cell: ({ row }) => <CurrencyDisplay amount={row.original.total_amount ?? 0} />,
  },
]

export default function OrdersPage() {
  const router = useRouter()
  const [page, setPage] = useState(0)
  const [search, setSearch] = useState('')

  // Reset to the first page whenever the search term changes.
  function onSearch(value: string) {
    setSearch(value)
    setPage(0)
  }

  const { data, isLoading } = useOrders({ page: page + 1, per_page: 20, q: search || undefined })

  const orders = Array.isArray(data) ? data : (data?.data ?? [])
  const pageCount = Array.isArray(data) ? 1 : (data ? Math.ceil((data.meta?.total ?? data.total ?? 0) / (data.meta?.per_page ?? data.per_page ?? 20)) : 0)

  return (
    <div className="space-y-6">
      <PageHeader
        title="Orders"
        actions={
          <Link
            href="/front-desk/new"
            className="flex items-center gap-2 px-4 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-xl hover:bg-[var(--color-brand-dark)] transition-colors"
          >
            <Plus size={15} strokeWidth={1.75} /> New Order
          </Link>
        }
      />

      <SearchInput
        value={search}
        onChange={onSearch}
        loading={isLoading}
        placeholder="Search by order #, customer name or phone…"
        className="max-w-md"
      />

      {isLoading ? (
        <TableSkeleton rows={8} cols={6} />
      ) : (
        <DataTable
          data={orders}
          columns={columns}
          pageCount={pageCount}
          pageIndex={page}
          onPageChange={setPage}
          onRowClick={(row) => router.push(`/orders/${row.id}`)}
        />
      )}
    </div>
  )
}
