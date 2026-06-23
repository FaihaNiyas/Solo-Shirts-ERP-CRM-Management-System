'use client'

import Link from 'next/link'
import { ClipboardCheck, Ruler, User, type LucideIcon } from 'lucide-react'
import { usePendingApprovals } from '@/lib/api/hooks/useMeasurements'
import { usePermission } from '@/lib/auth/permissions'
import { PageHeader } from '@/components/ui/page-header'
import { ApprovalChip } from '@/components/ui/approval-chip'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { EmptyState } from '@/components/ui/empty-state'
import { format } from 'date-fns'

export default function MeasurementsPage() {
  const { can } = usePermission()
  // Approval is optional in this ERP — only fetch/show the inbox for approvers,
  // otherwise the pending-approval poll 403s for measurement staff / Front Desk.
  const canApprove = can('measurements.approve')
  const { data: approvals = [], isLoading } = usePendingApprovals(canApprove)

  return (
    <div className="space-y-6">
      <PageHeader
        title="Measurements"
        subtitle={canApprove ? 'Pending approvals and measurement history' : 'Customer measurements'}
        actions={
          canApprove ? (
            <Link
              href="/measurements/approvals"
              className="flex items-center gap-2 px-4 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-xl hover:bg-[var(--color-brand-dark)] transition-colors"
            >
              <ClipboardCheck size={15} strokeWidth={1.75} />
              Approval Inbox
              {approvals.length > 0 && (
                <span className="flex items-center justify-center w-5 h-5 text-xs font-bold bg-white text-[var(--color-brand)] rounded-full">
                  {approvals.length}
                </span>
              )}
            </Link>
          ) : undefined
        }
      />

      {!canApprove && (
        <EmptyState
          title="Measurements are managed per customer"
          description="Open a customer to view or create their measurements — approval is not required."
          icon={Ruler as LucideIcon}
        />
      )}

      {canApprove && isLoading && <TableSkeleton rows={4} cols={3} />}

      {canApprove && !isLoading && approvals.length === 0 && (
        <EmptyState
          title="No pending approvals"
          description="All measurements are approved"
          icon={ClipboardCheck as LucideIcon}
        />
      )}

      {!isLoading && approvals.length > 0 && (
        <div className="space-y-2">
          <p className="text-sm font-semibold text-[var(--color-text-primary)]">
            Pending Approvals ({approvals.length})
          </p>
          {approvals.map((approval) => (
            <Link
              key={approval.id}
              href={`/measurements/${approval.version_id}`}
              className="flex items-center gap-4 p-4 rounded-xl border border-[var(--color-border)] bg-white hover:border-[var(--color-brand)] hover:shadow-sm transition-all"
            >
              <div className="w-9 h-9 rounded-full bg-[var(--color-brand-light)] flex items-center justify-center shrink-0">
                <User size={16} strokeWidth={1.75} className="text-[var(--color-brand)]" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-[var(--color-text-primary)] truncate">
                  {approval.customer_name ?? 'Customer'}
                </p>
                <p className="text-xs text-[var(--color-text-muted)]">
                  {approval.submitted_at ? format(new Date(approval.submitted_at), 'dd MMM yyyy') : '—'}
                </p>
              </div>
              <ApprovalChip status="pending_approval" />
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
