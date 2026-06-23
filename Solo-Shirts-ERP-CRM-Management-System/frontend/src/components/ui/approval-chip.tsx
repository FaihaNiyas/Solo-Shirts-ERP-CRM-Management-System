import { CheckCircle, XCircle, Clock } from 'lucide-react'
import { cn } from '@/lib/utils'

type ApprovalStatus = 'approved' | 'rejected' | 'pending_approval' | 'draft'

const CONFIG: Record<ApprovalStatus, { label: string; icon: React.ElementType; className: string }> = {
  approved: {
    label: 'Approved',
    icon: CheckCircle,
    className: 'bg-green-50 text-green-700 border border-green-200',
  },
  rejected: {
    label: 'Rejected',
    icon: XCircle,
    className: 'bg-red-50 text-red-700 border border-red-200',
  },
  pending_approval: {
    label: 'Pending Approval',
    icon: Clock,
    className: 'bg-amber-50 text-amber-700 border border-amber-200',
  },
  draft: {
    label: 'Draft',
    icon: Clock,
    className: 'bg-gray-50 text-gray-500 border border-gray-200',
  },
}

interface ApprovalChipProps {
  status: ApprovalStatus | string
  showIcon?: boolean
  className?: string
}

export function ApprovalChip({ status, showIcon = true, className }: ApprovalChipProps) {
  const cfg = CONFIG[status as ApprovalStatus] ?? CONFIG.draft
  const Icon = cfg.icon

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
        cfg.className,
        className,
      )}
    >
      {showIcon && <Icon size={12} strokeWidth={2} />}
      {cfg.label}
    </span>
  )
}
