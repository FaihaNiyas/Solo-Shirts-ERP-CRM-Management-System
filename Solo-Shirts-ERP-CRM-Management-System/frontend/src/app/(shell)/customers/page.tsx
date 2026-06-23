'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import type { ColumnDef } from '@tanstack/react-table'
import { PageHeader } from '@/components/ui/page-header'
import { DataTable } from '@/components/ui/data-table'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { useCustomers } from '@/lib/api/hooks/useCustomers'

// Row shape as returned by the list endpoint (schema only has full detail fields)
interface CustomerRow {
  id: number
  customer_code?: string
  name?: string
  phone?: string | null
  phone_last4?: string
  last_measurement_at?: string | null
}

const columns: ColumnDef<CustomerRow, unknown>[] = [
  { accessorKey: 'name', header: 'Name' },
  {
    accessorKey: 'customer_code',
    header: 'Code',
    cell: ({ row }) => <span className="font-mono text-xs">{row.original.customer_code ?? '—'}</span>,
  },
  {
    accessorKey: 'phone_last4',
    header: 'Phone',
    cell: ({ row }) =>
      row.original.phone ?? (row.original.phone_last4 ? `••••${row.original.phone_last4}` : '—'),
  },
  {
    accessorKey: 'last_measurement_at',
    header: 'Last Measurement',
    cell: ({ row }) =>
      row.original.last_measurement_at
        ? new Date(row.original.last_measurement_at).toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
          })
        : '—',
  },
]

// Server pagination envelope shape (the list endpoint returns PaginatedData).
interface PaginatedRows {
  data?: CustomerRow[]
  total?: number
  per_page?: number
  meta?: { total?: number; per_page?: number }
}

const PER_PAGE = 20

export default function CustomersPage() {
  const router = useRouter()
  const [search, setSearch] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [page, setPage] = useState(0)

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(search), 300)
    return () => clearTimeout(t)
  }, [search])

  // A new search must start from the first page, else we can land past the end.
  useEffect(() => {
    setPage(0)
  }, [debouncedSearch])

  // Use shared hook — shares the cache with mutations + customer detail prefetch
  const { data, isLoading } = useCustomers({
    page: page + 1,
    per_page: PER_PAGE,
    ...(debouncedSearch ? { search: debouncedSearch } : {}),
  })
  const paginated = data as unknown as PaginatedRows | undefined
  const customers = paginated?.data ?? []
  const total = paginated?.meta?.total ?? paginated?.total ?? 0
  const perPage = paginated?.meta?.per_page ?? paginated?.per_page ?? PER_PAGE
  const pageCount = paginated ? Math.max(1, Math.ceil(total / perPage)) : 1

  return (
    <div className="space-y-6">
      <PageHeader title="Customers" />

      <input
        type="search"
        value={search}
        onChange={(e) => setSearch(e.target.value)}
        placeholder="Search by name or phone…"
        className="h-9 w-72 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
      />

      {isLoading ? (
        <TableSkeleton rows={8} cols={4} />
      ) : (
        <DataTable
          data={customers}
          columns={columns}
          pageCount={pageCount}
          pageIndex={page}
          pageSize={perPage}
          onPageChange={setPage}
          onRowClick={(row: CustomerRow) => router.push(`/customers/${row.id}`)}
        />
      )}
    </div>
  )
}
