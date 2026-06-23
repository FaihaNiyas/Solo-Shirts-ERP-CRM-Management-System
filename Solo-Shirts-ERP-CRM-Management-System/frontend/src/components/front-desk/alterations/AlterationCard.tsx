'use client'

import Link from 'next/link'
import { format } from 'date-fns'
import { ArrowRight, Scissors } from 'lucide-react'
import { formatINR } from '@/lib/utils'
import {
  ALTERATION_ISSUE_LABELS,
  ALTERATION_STATUS_LABELS,
  type AlterationIssueType,
  type AlterationListRow,
} from '@/lib/api/hooks/useAlterations'

const STATUS_CLS: Record<string, string> = {
  intake: 'bg-amber-50 text-amber-800',
  approved: 'bg-blue-50 text-blue-700',
  in_alteration: 'bg-indigo-50 text-indigo-700',
  ready: 'bg-green-100 text-green-700',
  delivered: 'bg-green-50 text-green-600',
  cancelled: 'bg-gray-100 text-gray-500',
}

export function AlterationCard({ alteration }: { alteration: AlterationListRow }) {
  const a = alteration

  return (
    <Link
      href={`/front-desk/alterations/${a.id}`}
      className="block rounded-2xl border border-[var(--color-border)] bg-white p-4 hover:border-[var(--color-border-mid)] transition-colors"
    >
      <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
        <span className="inline-flex items-center gap-1.5 text-sm font-semibold text-[var(--color-text-primary)]">
          <Scissors size={14} strokeWidth={1.75} className="text-[var(--color-brand)]" />
          {ALTERATION_ISSUE_LABELS[a.issue_type as AlterationIssueType] ?? a.issue_type}
        </span>
        <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${STATUS_CLS[a.status] ?? 'bg-gray-100 text-gray-600'}`}>
          {ALTERATION_STATUS_LABELS[a.status] ?? a.status}
        </span>
        {a.priority === 'urgent' && (
          <span className="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-600">Urgent</span>
        )}
        <ArrowRight size={15} strokeWidth={2} className="ml-auto text-[var(--color-text-muted)]" />
      </div>

      <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
        {a.order_code && <span className="ss-mono text-[var(--color-text-secondary)]">{a.order_code}</span>}
        {a.item_code && <span className="ss-mono text-[var(--color-text-muted)]">{a.item_code}</span>}
        <span className="text-[var(--color-text-secondary)]">{a.customer_name ?? '—'}</span>
        {(a.phone ?? a.phone_masked) && (
          <span className="text-xs text-[var(--color-text-muted)]">{a.phone ?? a.phone_masked}</span>
        )}
      </div>

      {a.issue_preview && (
        <p className="mt-1.5 text-xs text-[var(--color-text-muted)] line-clamp-2">{a.issue_preview}</p>
      )}

      <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-[var(--color-text-muted)]">
        <span>{a.created_at ? format(new Date(a.created_at), 'dd MMM yyyy, HH:mm') : '—'}</span>
        {a.charge_required && (
          <span className="text-[var(--color-text-secondary)]">
            Est. {a.estimated_charge != null ? formatINR(a.estimated_charge) : '—'}
          </span>
        )}
      </div>
    </Link>
  )
}
