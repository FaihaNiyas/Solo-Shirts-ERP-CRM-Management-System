'use client'

import { useState } from 'react'
import { format } from 'date-fns'
import { Scissors } from 'lucide-react'
import { usePermission } from '@/lib/auth/permissions'
import { formatINR } from '@/lib/utils'
import { useAlterations, ALTERATION_ISSUE_LABELS, ALTERATION_STATUS_LABELS, type AlterationIssueType } from '@/lib/api/hooks/useAlterations'
import { AlterationIntakeModal, type AlterationIntakeItem } from './AlterationIntakeModal'

interface SectionItem {
  id: number
  item_code: string
  product_type?: string | null
  state?: string | null
}

const STATUS_CLS: Record<string, string> = {
  intake: 'bg-amber-50 text-amber-800',
  approved: 'bg-blue-50 text-blue-700',
  in_alteration: 'bg-indigo-50 text-indigo-700',
  ready: 'bg-green-100 text-green-700',
  delivered: 'bg-green-50 text-green-600',
  cancelled: 'bg-gray-100 text-gray-500',
}

export function OrderAlterationsSection({
  orderId,
  items,
}: {
  orderId: number
  items: SectionItem[]
}) {
  const { can } = usePermission()
  const canView = can('alterations.view')
  const canCreate = can('alterations.create')

  const delivered = items.filter((i) => i.state === 'delivered')
  const [open, setOpen] = useState(false)

  // Only relevant once something has actually been delivered, and only for staff
  // who hold the narrow alteration permission.
  const { data, isLoading } = useAlterations({ order_id: orderId }, canView && delivered.length > 0)

  if (!canView || delivered.length === 0) return null

  const intakeItems: AlterationIntakeItem[] = delivered.map((i) => ({
    id: i.id,
    item_code: i.item_code,
    product_type: i.product_type,
  }))

  return (
    <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-base font-semibold text-[var(--color-text-primary)]">Customer alterations (after delivery)</h2>
          <p className="mt-0.5 text-xs text-[var(--color-text-muted)]">
            Post-delivery fitting/stitching fixes — separate from internal QC rework.
          </p>
        </div>
        {canCreate && (
          <button
            type="button"
            onClick={() => setOpen(true)}
            className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-3 h-9 text-sm font-medium text-white hover:bg-[var(--color-brand-dark)] transition-colors"
          >
            <Scissors size={15} strokeWidth={1.75} /> Create Alteration
          </button>
        )}
      </div>

      {isLoading ? (
        <p className="py-4 text-center text-sm text-[var(--color-text-muted)]">Loading…</p>
      ) : !data || data.length === 0 ? (
        <p className="py-4 text-center text-sm text-[var(--color-text-muted)]">No alteration requests for this order.</p>
      ) : (
        <div className="space-y-2">
          {data.map((a) => (
            <div key={a.id} className="rounded-lg border border-[var(--color-border)] px-3 py-2">
              <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                <span className="ss-mono text-[var(--color-text-primary)]">{a.item_code ?? '—'}</span>
                <span className="font-medium text-[var(--color-text-primary)]">
                  {ALTERATION_ISSUE_LABELS[a.issue_type as AlterationIssueType] ?? a.issue_type}
                </span>
                <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${STATUS_CLS[a.status] ?? 'bg-gray-100 text-gray-600'}`}>
                  {ALTERATION_STATUS_LABELS[a.status] ?? a.status}
                </span>
                {a.priority === 'urgent' && (
                  <span className="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-600">Urgent</span>
                )}
                <span className="ml-auto text-xs text-[var(--color-text-muted)]">
                  {a.created_at ? format(new Date(a.created_at), 'dd MMM, HH:mm') : ''}
                </span>
              </div>
              <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-[var(--color-text-muted)]">
                {a.issue_preview && <span className="truncate">{a.issue_preview}</span>}
                {a.charge_required && (
                  <span className="text-[var(--color-text-secondary)]">
                    Est. {a.estimated_charge != null ? formatINR(a.estimated_charge) : '—'}
                  </span>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      <AlterationIntakeModal open={open} onClose={() => setOpen(false)} items={intakeItems} />
    </section>
  )
}
