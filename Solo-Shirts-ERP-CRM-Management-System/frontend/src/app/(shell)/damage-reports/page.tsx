'use client'

import { useMemo, useState } from 'react'
import { toast } from 'sonner'
import type { ColumnDef } from '@tanstack/react-table'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { queryKeys } from '@/lib/query/keys'
import { usePermission, ROLES } from '@/lib/auth/permissions'
import { PageHeader } from '@/components/ui/page-header'
import { DataTable } from '@/components/ui/data-table'
import { StatusBadge } from '@/components/ui/status-badge'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { format } from 'date-fns'

interface DamageReport {
  id: number
  fabric_roll_number?: string
  damage_type?: string
  metres?: number
  reported_by?: string
  status?: string
  created_at?: string
}

export default function DamageReportsPage() {
  const { is, can } = usePermission()
  const isOwner = is(ROLES.OWNER)
  const hasAccess = can('damage_reports.view') || isOwner
  const qc = useQueryClient()

  const { data: reports = [], isLoading } = useQuery({
    queryKey: queryKeys.damageReports(),
    queryFn: () => apiGet<DamageReport[]>(ENDPOINTS.damageReports),
    select: (res) => res.data,
    enabled: hasAccess,
  })

  const [action, setAction] = useState<{ type: 'approve' | 'reject'; id: number } | null>(null)

  const approveMutation = useMutation({
    mutationFn: (id: number) => apiMutate('post', ENDPOINTS.approveDamage(id), {}),
    onSuccess: () => qc.invalidateQueries({ queryKey: queryKeys.damageReports() }),
  })
  const rejectMutation = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) =>
      apiMutate('post', ENDPOINTS.rejectDamage(id), { reason }),
    onSuccess: () => qc.invalidateQueries({ queryKey: queryKeys.damageReports() }),
  })

  // Defined before the access guard so the hook order is stable across renders.
  const columns: ColumnDef<DamageReport, unknown>[] = useMemo(() => [
    { accessorKey: 'fabric_roll_number', header: 'Roll #', cell: ({ row }) => <span className="font-mono text-xs">{row.original.fabric_roll_number ?? '—'}</span> },
    { accessorKey: 'damage_type', header: 'Type' },
    { accessorKey: 'metres', header: 'Metres', cell: ({ row }) => <span className="font-mono text-sm">{row.original.metres ?? 0}m</span> },
    { accessorKey: 'reported_by', header: 'Reported By' },
    { id: 'status', header: 'Status', cell: ({ row }) => <StatusBadge status={row.original.status ?? 'pending_approval'} /> },
    { accessorKey: 'created_at', header: 'Date', cell: ({ row }) => row.original.created_at ? format(new Date(row.original.created_at), 'dd MMM yyyy') : '—' },
    ...(isOwner ? [{
      id: 'actions',
      header: 'Actions',
      cell: ({ row }: { row: { original: DamageReport } }) => row.original.status === 'pending' ? (
        <div className="flex gap-1.5">
          <button onClick={() => setAction({ type: 'approve', id: row.original.id })} className="px-2 py-1 text-xs border border-green-400 text-green-600 rounded hover:bg-green-50 transition-colors">Approve</button>
          <button onClick={() => setAction({ type: 'reject', id: row.original.id })} className="px-2 py-1 text-xs border border-red-300 text-red-500 rounded hover:bg-red-50 transition-colors">Reject</button>
        </div>
      ) : null,
    } as ColumnDef<DamageReport, unknown>] : []),
  ], [isOwner])

  if (!hasAccess) {
    return (
      <div className="flex flex-col items-center justify-center h-64 gap-3 text-center">
        <p className="text-lg font-semibold text-red-600">Access Denied</p>
        <p className="text-sm text-muted-foreground">You do not have permission to view damage reports.</p>
      </div>
    )
  }

  async function handleConfirm(reason?: string) {
    if (!action) return
    try {
      if (action.type === 'approve') {
        await approveMutation.mutateAsync(action.id)
        toast.success('Damage report approved')
      } else {
        await rejectMutation.mutateAsync({ id: action.id, reason: reason ?? '' })
        toast.success('Damage report rejected')
      }
      setAction(null)
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Damage Reports" />
      {isLoading ? <TableSkeleton rows={5} cols={6} /> : <DataTable data={reports} columns={columns} />}
      <ConfirmDialog
        open={action !== null}
        onClose={() => setAction(null)}
        onConfirm={handleConfirm}
        title={action?.type === 'approve' ? 'Approve Damage Report' : 'Reject Damage Report'}
        description="Confirm this action?"
        variant={action?.type === 'reject' ? 'destructive' : 'info'}
        requireReason={action?.type === 'reject'}
      />
    </div>
  )
}
