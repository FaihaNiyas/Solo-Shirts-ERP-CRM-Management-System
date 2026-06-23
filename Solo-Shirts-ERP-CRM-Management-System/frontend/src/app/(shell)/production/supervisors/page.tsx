'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { useQuery } from '@tanstack/react-query'
import { Shield, X, UserPlus } from 'lucide-react'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { PageHeader } from '@/components/ui/page-header'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { FormField } from '@/components/ui/form-field'
import { usePermission } from '@/lib/auth/permissions'
import {
  useStageSupervisors,
  useAssignSupervisor,
  useUnassignSupervisor,
  type StageSupervisor,
} from '@/lib/api/hooks/useProduction'
import { productionStateLabel } from '@/lib/orders/productionState'

// Mirrors ProductionStageSupervisor::SECTIONS on the backend.
const SECTIONS = [
  'fabric_allocated', 'cutting', 'tailoring', 'kaja_button',
  'finishing', 'qc', 'rework', 'packing', 'ready_for_delivery',
]

interface UserRef {
  id: number
  name?: string
  roles?: string[]
}

const selectCls =
  'w-full h-10 px-3 rounded-lg border border-[var(--color-border-mid)] text-sm bg-white ' +
  'text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]'

export default function ProductionSupervisorsPage() {
  const { can } = usePermission()
  const allowed = can('production.supervisor.assign')

  const { data: supervisors = [], isLoading } = useStageSupervisors()
  const { data: users = [] } = useQuery<UserRef[]>({
    queryKey: ['production', 'supervisor-users'],
    queryFn: () => apiGet<UserRef[]>(ENDPOINTS.users).then((r) => r.data),
    enabled: allowed,
  })
  const assign = useAssignSupervisor()
  const unassign = useUnassignSupervisor()

  const [userId, setUserId] = useState(0)
  const [stage, setStage] = useState(SECTIONS[0])

  async function handleAssign() {
    if (!userId) return
    try {
      await assign.mutateAsync({ user_id: userId, stage })
      toast.success('Supervisor assigned')
      setUserId(0)
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Could not assign supervisor')
    }
  }

  async function handleRemove(id: number) {
    try {
      await unassign.mutateAsync(id)
      toast.success('Supervisor removed')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Could not remove supervisor')
    }
  }

  if (!allowed) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center">
        <Shield size={40} strokeWidth={1.25} className="text-[var(--color-text-muted)]" />
        <p className="text-sm text-[var(--color-text-muted)]">
          You don&apos;t have permission to manage section supervisors.
        </p>
      </div>
    )
  }

  const byStage: Record<string, StageSupervisor[]> = {}
  for (const s of supervisors) (byStage[s.stage] ??= []).push(s)

  return (
    <div className="space-y-6">
      <PageHeader title="Section Supervisors" subtitle="Assign a supervisor to each production section" />

      {/* Assign form */}
      <div className="rounded-xl border border-[var(--color-border)] bg-white p-4 grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 sm:items-end">
        <FormField label="User">
          <select value={userId} onChange={(e) => setUserId(Number(e.target.value))} className={selectCls}>
            <option value={0} disabled>
              Select user…
            </option>
            {users.map((u) => (
              <option key={u.id} value={u.id}>
                {u.name ?? `#${u.id}`}
                {u.roles?.[0] ? ` · ${u.roles[0]}` : ''}
              </option>
            ))}
          </select>
        </FormField>
        <FormField label="Section">
          <select value={stage} onChange={(e) => setStage(e.target.value)} className={selectCls}>
            {SECTIONS.map((s) => (
              <option key={s} value={s}>
                {productionStateLabel(s)}
              </option>
            ))}
          </select>
        </FormField>
        <button
          onClick={handleAssign}
          disabled={!userId || assign.isPending}
          className="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-lg bg-[var(--color-brand)] text-white text-sm font-medium hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
        >
          <UserPlus size={15} /> Assign
        </button>
      </div>

      {/* Assignments grouped by section */}
      {isLoading ? (
        <TableSkeleton rows={6} cols={2} />
      ) : (
        <div className="space-y-3">
          {SECTIONS.map((s) => {
            const rows = byStage[s] ?? []
            return (
              <div key={s} className="rounded-xl border border-[var(--color-border)] bg-white p-4">
                <p className="text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)] mb-2">
                  {productionStateLabel(s)}
                </p>
                <div className="flex flex-wrap gap-2">
                  {rows.length === 0 && (
                    <span className="text-sm text-[var(--color-text-muted)]">No supervisor assigned</span>
                  )}
                  {rows.map((sup) => (
                    <span
                      key={sup.id}
                      className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-sm bg-[var(--color-brand-light)] text-[var(--color-brand-dark)]"
                    >
                      {sup.user_name ?? `#${sup.user_id}`}
                      <button
                        onClick={() => handleRemove(sup.id)}
                        disabled={unassign.isPending}
                        className="hover:text-red-600 disabled:opacity-50"
                        aria-label="Remove supervisor"
                      >
                        <X size={13} />
                      </button>
                    </span>
                  ))}
                </div>
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}
