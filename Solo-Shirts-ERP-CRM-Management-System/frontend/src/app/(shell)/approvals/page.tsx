'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiPost, generateIdempotencyKey } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { PageHeader } from '@/components/ui/page-header'
import { StatusBadge } from '@/components/ui/status-badge'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { format } from 'date-fns'

type ApprovalTab = 'measurements' | 'damage'

const TABS: { key: ApprovalTab; label: string }[] = [
  { key: 'measurements', label: 'Measurements' },
  { key: 'damage', label: 'Damage Reports' },
]

interface ApprovalItem {
  id: number
  title: string
  description?: string
  meta?: string
  created_at?: string
  status?: string
}

// --- Per-tab data sources (real, tested backend endpoints) ----------------

interface PendingVersion {
  id: number
  profile_id: number
  version_number: number
  significant_change?: boolean
  created_by?: number
  created_at?: string
}

interface DamageReport {
  id: number
  fabric_roll_id?: number
  stage?: string
  damage_type?: string
  quantity_lost_metres?: number | string
  status?: string
  reported_at?: string
}

function useMeasurementApprovals(enabled: boolean) {
  return useQuery<ApprovalItem[]>({
    queryKey: ['approvals', 'measurements'],
    enabled,
    queryFn: () =>
      apiGet<PendingVersion[]>(ENDPOINTS.pendingApprovals).then((r) =>
        r.data.map((v) => ({
          id: v.id,
          title: `Measurement v${v.version_number}`,
          description: v.significant_change ? 'Significant change — needs approval' : 'Minor revision',
          meta: v.created_by ? `By #${v.created_by}` : undefined,
          created_at: v.created_at,
          status: 'pending',
        })),
      ),
  })
}

function useDamageApprovals(enabled: boolean) {
  return useQuery<ApprovalItem[]>({
    queryKey: ['approvals', 'damage'],
    enabled,
    queryFn: () =>
      apiGet<DamageReport[]>(ENDPOINTS.damageReports, { status: 'pending' }).then((r) =>
        r.data.map((d) => ({
          id: d.id,
          title: `${d.damage_type ?? 'Damage'} · roll #${d.fabric_roll_id}`,
          description: `${d.quantity_lost_metres ?? 0} m lost at ${d.stage ?? 'unknown stage'}`,
          created_at: d.reported_at,
          status: d.status ?? 'pending',
        })),
      ),
  })
}

export default function ApprovalsPage() {
  const [tab, setTab] = useState<ApprovalTab>('measurements')
  const qc = useQueryClient()

  const measurements = useMeasurementApprovals(tab === 'measurements')
  const damage = useDamageApprovals(tab === 'damage')

  const items = tab === 'measurements' ? measurements.data ?? [] : damage.data ?? []
  const isLoading = tab === 'measurements' ? measurements.isLoading : damage.isLoading

  const [approveTarget, setApproveTarget] = useState<ApprovalItem | null>(null)
  const [rejectTarget, setRejectTarget] = useState<ApprovalItem | null>(null)

  function approveEndpoint(id: number): string {
    return tab === 'measurements' ? ENDPOINTS.approveMeasurement(id) : ENDPOINTS.approveDamage(id)
  }
  function rejectEndpoint(id: number): string {
    return tab === 'measurements' ? ENDPOINTS.rejectMeasurement(id) : ENDPOINTS.rejectDamage(id)
  }

  function invalidate() {
    void qc.invalidateQueries({ queryKey: ['approvals'] })
    void qc.invalidateQueries({ queryKey: ['measurements'] })
    void qc.invalidateQueries({ queryKey: ['damage-reports'] })
  }

  const approveMutation = useMutation({
    mutationFn: (item: ApprovalItem) =>
      apiPost(approveEndpoint(item.id), {}, { headers: { 'Idempotency-Key': generateIdempotencyKey() } }),
    onSuccess: () => {
      invalidate()
      setApproveTarget(null)
      toast.success('Approved')
    },
    onError: (err: unknown) => toast.error((err as { message?: string })?.message ?? 'Failed'),
  })

  const rejectMutation = useMutation({
    mutationFn: ({ item, reason }: { item: ApprovalItem; reason: string }) =>
      apiPost(rejectEndpoint(item.id), { reason }, { headers: { 'Idempotency-Key': generateIdempotencyKey() } }),
    onSuccess: () => {
      invalidate()
      setRejectTarget(null)
      toast.success('Rejected')
    },
    onError: (err: unknown) => toast.error((err as { message?: string })?.message ?? 'Failed'),
  })

  const pendingCount = items.length

  return (
    <div className="space-y-6">
      <PageHeader
        title="Approvals"
        subtitle={pendingCount > 0 ? `${pendingCount} pending` : 'All caught up'}
      />

      {/* Tabs */}
      <div className="border-b border-[var(--color-border)]">
        <div className="flex gap-0">
          {TABS.map(({ key, label }) => (
            <button
              key={key}
              onClick={() => setTab(key)}
              className={`px-4 py-2.5 text-sm font-medium border-b-2 transition-colors ${
                tab === key
                  ? 'border-[var(--color-brand)] text-[var(--color-brand)]'
                  : 'border-transparent text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'
              }`}
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      {isLoading && <TableSkeleton rows={4} cols={4} />}

      {!isLoading && items.length === 0 && (
        <div className="py-16 text-center text-sm text-[var(--color-text-muted)]">
          No pending {tab === 'measurements' ? 'measurement' : 'damage'} approvals
        </div>
      )}

      {!isLoading && items.length > 0 && (
        <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
          <div className="divide-y divide-[var(--color-border)] bg-white">
            {items.map((item) => (
              <div key={item.id} className="flex items-start gap-4 px-4 py-4">
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-[var(--color-text-primary)]">{item.title}</p>
                  {item.description && (
                    <p className="text-xs text-[var(--color-text-muted)] mt-0.5">{item.description}</p>
                  )}
                  <div className="flex items-center gap-3 mt-1">
                    {item.meta && (
                      <span className="text-xs text-[var(--color-text-muted)]">{item.meta}</span>
                    )}
                    {item.created_at && (
                      <span className="text-xs text-[var(--color-text-muted)]">
                        {format(new Date(item.created_at), 'dd MMM yyyy HH:mm')}
                      </span>
                    )}
                  </div>
                </div>
                <StatusBadge status={item.status ?? 'pending'} />
                <div className="flex gap-2 shrink-0">
                  <button
                    onClick={() => setApproveTarget(item)}
                    className="px-3 py-1.5 text-xs font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                  >
                    Approve
                  </button>
                  <button
                    onClick={() => setRejectTarget(item)}
                    className="px-3 py-1.5 text-xs font-medium border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                  >
                    Reject
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      <ConfirmDialog
        open={approveTarget !== null}
        onClose={() => setApproveTarget(null)}
        onConfirm={async () => { if (approveTarget) await approveMutation.mutateAsync(approveTarget) }}
        title="Approve"
        description={`Approve "${approveTarget?.title ?? 'this item'}"?`}
        variant="info"
        loading={approveMutation.isPending}
      />

      <ConfirmDialog
        open={rejectTarget !== null}
        onClose={() => setRejectTarget(null)}
        onConfirm={async (reason) => { if (rejectTarget) await rejectMutation.mutateAsync({ item: rejectTarget, reason: reason ?? '' }) }}
        title="Reject"
        description="Provide a reason (min. 10 characters)."
        variant="destructive"
        requireReason
        loading={rejectMutation.isPending}
      />
    </div>
  )
}
