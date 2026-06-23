'use client'

import { useState } from 'react'
import { Check, X } from 'lucide-react'
import { toast } from 'sonner'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { apiMutate, generateIdempotencyKey } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { usePendingApprovals } from '@/lib/api/hooks/useMeasurements'
import { queryKeys } from '@/lib/query/keys'
import { PageHeader } from '@/components/ui/page-header'
import { ApprovalChip } from '@/components/ui/approval-chip'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { EmptyState } from '@/components/ui/empty-state'
import { format } from 'date-fns'

export default function ApprovalsPage() {
  const qc = useQueryClient()
  const { data: approvals = [], isLoading } = usePendingApprovals()
  const [rejectTarget, setRejectTarget] = useState<number | null>(null)

  const approveMutation = useMutation({
    mutationFn: (versionId: number) =>
      apiMutate('post', ENDPOINTS.approveMeasurement(versionId), {}, generateIdempotencyKey()),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.pendingApprovals() })
      toast.success('Measurement approved')
    },
    onError: (err: unknown) => {
      toast.error((err as { message?: string })?.message ?? 'Failed to approve')
    },
  })

  const rejectMutation = useMutation({
    mutationFn: ({ versionId, reason }: { versionId: number; reason: string }) =>
      apiMutate('post', ENDPOINTS.rejectMeasurement(versionId), { note: reason }, generateIdempotencyKey()),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.pendingApprovals() })
      setRejectTarget(null)
      toast.success('Measurement rejected')
    },
    onError: (err: unknown) => {
      toast.error((err as { message?: string })?.message ?? 'Failed to reject')
    },
  })

  return (
    <div className="space-y-6">
      <PageHeader
        title="Measurement Approvals"
        subtitle={`${approvals.length} pending`}
      />

      {isLoading && <TableSkeleton rows={4} cols={4} />}

      {!isLoading && approvals.length === 0 && (
        <EmptyState title="All clear" description="No measurements pending approval" />
      )}

      {!isLoading && approvals.length > 0 && (
        <div className="space-y-3">
          {approvals.map((approval) => (
            <div
              key={approval.id}
              className="rounded-xl border border-[var(--color-border)] bg-white overflow-hidden"
            >
              <div className="flex items-center justify-between p-4">
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-[var(--color-text-primary)]">
                    {approval.customer_name ?? 'Customer'}
                  </p>
                  <p className="text-xs text-[var(--color-text-muted)]">
                    Submitted{' '}
                    {approval.submitted_at
                      ? format(new Date(approval.submitted_at), 'dd MMM yyyy, HH:mm')
                      : '—'}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <ApprovalChip status="pending_approval" />
                  <button
                    onClick={() => approveMutation.mutate(approval.version_id)}
                    disabled={approveMutation.isPending}
                    className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 disabled:opacity-50 transition-colors"
                  >
                    <Check size={13} strokeWidth={2} /> Approve
                  </button>
                  <button
                    onClick={() => setRejectTarget(approval.version_id)}
                    className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors"
                  >
                    <X size={13} strokeWidth={2} /> Reject
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      <ConfirmDialog
        open={rejectTarget !== null}
        onClose={() => setRejectTarget(null)}
        onConfirm={async (reason) => {
          if (rejectTarget) await rejectMutation.mutateAsync({ versionId: rejectTarget, reason: reason ?? '' })
        }}
        title="Reject Measurement"
        description="Provide a reason for rejecting this measurement version."
        variant="destructive"
        requireReason
        loading={rejectMutation.isPending}
      />
    </div>
  )
}
