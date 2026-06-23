'use client'

import { use } from 'react'
import Link from 'next/link'
import { format } from 'date-fns'
import { ArrowLeft, ExternalLink, Info, Lock } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { InfoGrid } from '@/components/ui/info-grid'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { formatINR } from '@/lib/utils'
import {
  ALTERATION_ISSUE_LABELS,
  ALTERATION_STATUS_LABELS,
  useAlteration,
  type AlterationIssueType,
} from '@/lib/api/hooks/useAlterations'
import { AlterationStatusActions } from '@/components/front-desk/alterations/AlterationStatusActions'

const STATUS_CLS: Record<string, string> = {
  intake: 'bg-amber-50 text-amber-800',
  approved: 'bg-blue-50 text-blue-700',
  in_alteration: 'bg-indigo-50 text-indigo-700',
  ready: 'bg-green-100 text-green-700',
  delivered: 'bg-green-50 text-green-600',
  cancelled: 'bg-gray-100 text-gray-500',
}

export default function AlterationDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const alterationId = parseInt(id)
  const { data: a, isLoading } = useAlteration(alterationId)

  if (isLoading) return <TableSkeleton rows={5} cols={2} />
  if (!a) return <p className="text-sm text-[var(--color-text-muted)]">Alteration not found</p>

  return (
    <div className="space-y-6">
      <Link
        href="/front-desk/alterations"
        className="inline-flex items-center gap-1.5 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]"
      >
        <ArrowLeft size={15} strokeWidth={1.75} /> All alterations
      </Link>

      <PageHeader
        title={ALTERATION_ISSUE_LABELS[a.issue_type as AlterationIssueType] ?? a.issue_type}
        subtitle={a.customer_name ?? '—'}
        actions={
          <div className="flex items-center gap-2">
            <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${STATUS_CLS[a.status] ?? 'bg-gray-100 text-gray-600'}`}>
              {ALTERATION_STATUS_LABELS[a.status] ?? a.status}
            </span>
            {a.priority === 'urgent' && (
              <span className="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">Urgent</span>
            )}
          </div>
        }
      />

      {/* This is a customer alteration, not internal QC rework. */}
      <div className="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <Info size={16} strokeWidth={1.75} className="mt-0.5 shrink-0" />
        <span>
          Customer alteration after delivery. The original order, invoice, and production stages are
          unchanged. Estimated charge only — payment is handled separately.
        </span>
      </div>

      <InfoGrid
        items={[
          { label: 'Order', value: a.order_code ?? '—' },
          { label: 'Sub-order', value: a.item_code ?? '—' },
          { label: 'Customer', value: a.customer_name ?? '—' },
          { label: 'Phone', value: a.phone ?? a.phone_masked ?? '—' },
          { label: 'Product', value: a.product_type ?? '—' },
          { label: 'Fabric', value: a.fabric ?? '—' },
          { label: 'Style', value: a.style ?? '—' },
          { label: 'Fit', value: a.fit ?? '—' },
          {
            label: 'Estimated charge',
            value: a.charge_required ? (a.estimated_charge != null ? formatINR(a.estimated_charge) : '—') : 'Not charged',
          },
          { label: 'Logged by', value: a.created_by ?? '—' },
          { label: 'Logged at', value: a.created_at ? format(new Date(a.created_at), 'dd MMM yyyy, HH:mm') : '—' },
        ]}
      />

      {/* Workflow — only allowed next transitions for this user are shown. */}
      <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3">
        <div className="flex items-center justify-between gap-3">
          <h2 className="text-sm font-semibold text-[var(--color-text-primary)]">Alteration status</h2>
          <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${STATUS_CLS[a.status] ?? 'bg-gray-100 text-gray-600'}`}>
            {ALTERATION_STATUS_LABELS[a.status] ?? a.status}
          </span>
        </div>
        {a.can_update_status ? (
          <AlterationStatusActions
            alterationId={a.id}
            currentStatus={a.status}
            allowedNext={a.allowed_next_statuses}
          />
        ) : (
          <p className="inline-flex items-center gap-1.5 text-xs text-[var(--color-text-muted)]">
            <Lock size={13} strokeWidth={1.75} />
            {a.status === 'delivered' || a.status === 'cancelled'
              ? `This alteration is ${ALTERATION_STATUS_LABELS[a.status] ?? a.status} — no further changes.`
              : 'You do not have permission to update this alteration.'}
          </p>
        )}
      </section>

      <section className="rounded-xl border border-[var(--color-border)] bg-white p-4">
        <h2 className="text-sm font-semibold text-[var(--color-text-primary)] mb-1.5">Issue described</h2>
        <p className="text-sm text-[var(--color-text-secondary)] whitespace-pre-wrap">{a.issue_description}</p>
      </section>

      {a.status_logs.length > 0 && (
        <section className="rounded-xl border border-[var(--color-border)] bg-white p-4">
          <h2 className="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Status history</h2>
          <ol className="space-y-3">
            {a.status_logs.map((log) => (
              <li key={log.id} className="flex gap-3">
                <span className="mt-1 h-2 w-2 shrink-0 rounded-full bg-[var(--color-brand)]" aria-hidden />
                <div className="min-w-0">
                  <p className="text-sm text-[var(--color-text-primary)]">
                    {ALTERATION_STATUS_LABELS[log.previous_status] ?? log.previous_status}
                    <span className="text-[var(--color-text-muted)]"> → </span>
                    <span className="font-medium">{ALTERATION_STATUS_LABELS[log.new_status] ?? log.new_status}</span>
                  </p>
                  <p className="text-xs text-[var(--color-text-muted)]">
                    {log.created_at ? format(new Date(log.created_at), 'dd MMM yyyy, HH:mm') : '—'}
                    {log.changed_by ? ` · ${log.changed_by}` : ''}
                  </p>
                  {log.notes && <p className="mt-0.5 text-xs text-[var(--color-text-secondary)]">{log.notes}</p>}
                </div>
              </li>
            ))}
          </ol>
        </section>
      )}

      {a.photo_url && (
        <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3">
          <h2 className="text-sm font-semibold text-[var(--color-text-primary)]">Reference photo</h2>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={a.photo_url} alt="Alteration reference" className="max-h-80 rounded-lg border border-[var(--color-border)]" />
          <a
            href={a.photo_url}
            target="_blank"
            rel="noreferrer"
            className="inline-flex items-center gap-1.5 text-xs font-medium text-[var(--color-brand-dark)] hover:underline"
          >
            Open full size <ExternalLink size={12} strokeWidth={1.75} />
          </a>
        </section>
      )}

    </div>
  )
}
