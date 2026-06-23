'use client'

import { useState } from 'react'
import { AlertTriangle, Loader2, Scissors, SearchX } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { SearchInput } from '@/components/ui/search-input'
import { useAlterations } from '@/lib/api/hooks/useAlterations'
import { AlterationCard } from '@/components/front-desk/alterations/AlterationCard'

const STATUS_FILTERS = [
  { value: '', label: 'All' },
  { value: 'intake', label: 'Intake' },
  { value: 'approved', label: 'Approved' },
  { value: 'in_alteration', label: 'In alteration' },
  { value: 'ready', label: 'Ready' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'cancelled', label: 'Cancelled' },
]

export default function AlterationsQueuePage() {
  const [q, setQ] = useState('')
  const [status, setStatus] = useState('')

  const { data, isFetching, isError, error } = useAlterations({
    q: q.trim() || undefined,
    status: status || undefined,
  })
  const err = error as { message?: string; request_id?: string } | null

  return (
    <div className="space-y-5">
      <PageHeader
        title="Alteration Requests"
        description="Customer alterations logged after delivery — separate from internal QC rework"
      />

      <div className="max-w-xl">
        <SearchInput value={q} onChange={setQ} placeholder="Order code / sub-order / phone" autoFocus />
      </div>

      <div className="flex flex-wrap gap-1.5">
        {STATUS_FILTERS.map((f) => (
          <button
            key={f.value}
            type="button"
            onClick={() => setStatus(f.value)}
            className={
              status === f.value
                ? 'rounded-full bg-[var(--color-brand)] px-3 h-8 text-xs font-medium text-white'
                : 'rounded-full border border-[var(--color-border-mid)] px-3 h-8 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]'
            }
          >
            {f.label}
          </button>
        ))}
      </div>

      {isFetching && (
        <div className="flex items-center justify-center gap-2 py-12 text-sm text-[var(--color-text-muted)]">
          <Loader2 size={16} className="animate-spin" /> Loading…
        </div>
      )}

      {isError && (
        <div className="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          <AlertTriangle size={16} strokeWidth={1.75} className="mt-0.5 shrink-0" />
          <div>
            <p>{err?.message ?? 'Could not load alterations.'}</p>
            {err?.request_id && <p className="text-xs opacity-75">request_id: {err.request_id}</p>}
          </div>
        </div>
      )}

      {!isFetching && !isError && data && data.length === 0 && (
        <div className="flex flex-col items-center gap-2 py-12 text-center text-sm text-[var(--color-text-muted)]">
          {q.trim() ? <SearchX size={26} strokeWidth={1.5} /> : <Scissors size={26} strokeWidth={1.5} />}
          {q.trim() ? `No alterations match “${q}”.` : 'No alteration requests yet.'}
        </div>
      )}

      {!isFetching && !isError && data && data.length > 0 && (
        <div className="space-y-3">
          {data.map((a) => (
            <AlterationCard key={a.id} alteration={a} />
          ))}
        </div>
      )}
    </div>
  )
}
