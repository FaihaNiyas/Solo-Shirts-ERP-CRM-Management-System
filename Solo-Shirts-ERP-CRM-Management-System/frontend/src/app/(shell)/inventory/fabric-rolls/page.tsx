'use client'

import { useMemo, useState } from 'react'
import { useRouter } from 'next/navigation'
import { AlertTriangle } from 'lucide-react'
import type { ColumnDef } from '@tanstack/react-table'
import { useFabricRolls, useFabricTypes } from '@/lib/api/hooks/useInventory'
import { PageHeader } from '@/components/ui/page-header'
import { DataTable } from '@/components/ui/data-table'
import { StatusBadge } from '@/components/ui/status-badge'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { usePermission } from '@/lib/auth/permissions'
import type { FabricRoll } from '@/lib/api/schemas/inventory'

/** Metre values arrive as formatted strings ("85.00") or numbers. */
function metres(v: number | string | undefined): string {
  return v === undefined || v === null ? '—' : `${v}m`
}

export default function FabricRollsPage() {
  const { can } = usePermission()
  const router = useRouter()
  const [page, setPage] = useState(0)
  const { data, isLoading } = useFabricRolls({ page: page + 1, per_page: 20 })
  const { data: types } = useFabricTypes()

  const typeName = useMemo(() => {
    const map = new Map<number, string>()
    for (const t of types ?? []) map.set(t.id, t.name)
    return map
  }, [types])

  const columns = useMemo<ColumnDef<FabricRoll, unknown>[]>(() => [
    {
      accessorKey: 'roll_code',
      header: 'Roll #',
      cell: ({ row }) => <span className="font-mono text-xs font-semibold text-[var(--color-text-primary)]">{row.original.roll_code ?? `#${row.original.id}`}</span>,
    },
    {
      id: 'fabric_type',
      header: 'Fabric Type',
      cell: ({ row }) => <span className="text-sm">{typeName.get(row.original.fabric_type_id) ?? `Type ${row.original.fabric_type_id}`}</span>,
    },
    { accessorKey: 'colour', header: 'Colour', cell: ({ row }) => <span className="text-sm">{row.original.colour ?? '—'}</span> },
    { id: 'remaining', header: 'Remaining', cell: ({ row }) => <span className="font-mono text-sm">{metres(row.original.remaining_metres)}</span> },
    { id: 'reserved', header: 'Reserved', cell: ({ row }) => <span className="font-mono text-sm text-[var(--color-brand)]">{metres(row.original.reserved_metres)}</span> },
    { id: 'consumed', header: 'Consumed', cell: ({ row }) => <span className="font-mono text-sm text-[var(--color-text-muted)]">{metres(row.original.consumed_metres)}</span> },
    { id: 'available', header: 'Available', cell: ({ row }) => <span className="font-mono text-sm text-green-600">{metres(row.original.available_metres)}</span> },
    {
      id: 'status',
      header: 'Status',
      cell: ({ row }) => (
        <div className="flex items-center gap-1.5">
          <StatusBadge status={row.original.status ?? 'active'} />
          {row.original.low_stock && (
            <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-[var(--color-warning)]">
              <AlertTriangle size={11} strokeWidth={1.75} /> Low
            </span>
          )}
        </div>
      ),
    },
  ], [typeName])

  if (!can('inventory.view')) {
    return (
      <div className="flex flex-col items-center justify-center h-64 gap-3 text-center">
        <p className="text-lg font-semibold text-red-600">Access Denied</p>
        <p className="text-sm text-muted-foreground">You do not have permission to view inventory.</p>
      </div>
    )
  }

  const rolls = Array.isArray(data) ? data : (data?.data ?? [])
  const pageCount = Array.isArray(data) ? 1 : (data ? Math.ceil((data.meta?.total ?? data.total ?? 0) / (data.meta?.per_page ?? data.per_page ?? 20)) : 0)

  return (
    <div className="space-y-6">
      <PageHeader
        title="Fabric Rolls"
        subtitle="Remaining · Reserved · Consumed · Available"
      />

      {isLoading ? (
        <TableSkeleton rows={8} cols={8} />
      ) : (
        <DataTable
          data={rolls}
          columns={columns}
          pageCount={pageCount}
          pageIndex={page}
          onPageChange={setPage}
          onRowClick={(row) => router.push(`/inventory/fabric-rolls/${row.id}`)}
        />
      )}
    </div>
  )
}
