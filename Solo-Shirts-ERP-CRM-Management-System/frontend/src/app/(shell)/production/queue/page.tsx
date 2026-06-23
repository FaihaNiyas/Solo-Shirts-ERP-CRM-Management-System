'use client'

import { useState } from 'react'
import Link from 'next/link'
import { AlertTriangle, ArrowRight, Box, Layers, Loader2, Search, Zap } from 'lucide-react'
import { format } from 'date-fns'
import { PageHeader } from '@/components/ui/page-header'
import { SearchInput } from '@/components/ui/search-input'
import { productionStateLabel } from '@/lib/api/hooks/useFrontDeskLookup'
import {
  useProductionQueue,
  useProductionCodeSearch,
  type ProductionQueueRow,
} from '@/lib/api/hooks/useProductionQueue'

const STAGES = [
  { value: '', label: 'All' },
  { value: 'fabric_allocated', label: 'Fabric Ready' },
  { value: 'cutting', label: 'Cutting' },
  { value: 'tailoring', label: 'Tailoring' },
  { value: 'kaja_button', label: 'Kaja / Button' },
  { value: 'finishing', label: 'Finishing' },
  { value: 'qc', label: 'QC' },
  { value: 'rework', label: 'Rework' },
  { value: 'packing', label: 'Packing' },
  { value: 'ready_for_delivery', label: 'Ready' },
]

export default function ProductionQueuePage() {
  const [stage, setStage] = useState('')
  const [code, setCode] = useState('')
  const searching = code.trim().length > 0

  const queue = useProductionQueue(searching ? {} : { stage: stage || undefined })
  const searched = useProductionCodeSearch(code, searching)

  const rows = searching ? searched.data : queue.data
  const loading = searching ? searched.isFetching : queue.isFetching
  const isError = searching ? searched.isError : queue.isError
  const err = (searching ? searched.error : queue.error) as { message?: string; request_id?: string } | null

  return (
    <div className="space-y-5">
      <PageHeader
        title="Production Queue"
        description="Confirmed sub-orders on the floor — search or open a workbench"
        actions={
          <Link href="/production" className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-10 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
            <Layers size={15} strokeWidth={1.75} /> Board view
          </Link>
        }
      />

      <div className="max-w-xl">
        <SearchInput value={code} onChange={setCode} placeholder="Search by item / box / order code" autoFocus />
      </div>

      {!searching && (
        <div className="flex flex-wrap gap-1.5">
          {STAGES.map((s) => (
            <button
              key={s.value}
              type="button"
              onClick={() => setStage(s.value)}
              className={
                stage === s.value
                  ? 'rounded-full bg-[var(--color-brand)] px-3 h-8 text-xs font-medium text-white'
                  : 'rounded-full border border-[var(--color-border-mid)] px-3 h-8 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]'
              }
            >
              {s.label}
            </button>
          ))}
        </div>
      )}

      {loading && (
        <div className="flex items-center justify-center gap-2 py-12 text-sm text-[var(--color-text-muted)]">
          <Loader2 size={16} className="animate-spin" /> Loading…
        </div>
      )}

      {isError && (
        <div className="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          <AlertTriangle size={16} strokeWidth={1.75} className="mt-0.5 shrink-0" />
          <div>
            <p>{err?.message ?? 'Could not load the queue.'}</p>
            {err?.request_id && <p className="text-xs opacity-75">request_id: {err.request_id}</p>}
          </div>
        </div>
      )}

      {!loading && !isError && rows && rows.length === 0 && (
        <div className="flex flex-col items-center gap-2 py-12 text-center text-sm text-[var(--color-text-muted)]">
          {searching ? <Search size={26} strokeWidth={1.5} /> : <Layers size={26} strokeWidth={1.5} />}
          {searching ? `Nothing matches “${code}”.` : 'No items in this stage.'}
        </div>
      )}

      {rows && rows.length > 0 && (
        <div className="space-y-2">
          {rows.map((r) => (
            <QueueRow key={r.item_id} row={r} />
          ))}
        </div>
      )}
    </div>
  )
}

function QueueRow({ row }: { row: ProductionQueueRow }) {
  return (
    <Link
      href={`/production/items/${row.item_id}`}
      className="flex flex-wrap items-center gap-3 rounded-2xl border border-[var(--color-border)] bg-white p-4 hover:border-[var(--color-border-mid)] transition-colors"
    >
      <div className="min-w-0 flex-1">
        <div className="flex flex-wrap items-center gap-2">
          <span className="ss-mono text-base font-semibold text-[var(--color-text-primary)]">{row.item_code}</span>
          <span className="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700">{productionStateLabel(row.current_stage)}</span>
          {row.is_rush && (
            <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-[var(--color-warning)]">
              <Zap size={11} strokeWidth={1.75} /> Rush
            </span>
          )}
          {row.production_box_code && (
            <span className="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">
              <Box size={11} strokeWidth={1.75} /> Box {row.production_box_code}
            </span>
          )}
          {row.fabric_status === 'reserved' && (
            <span className="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700">Fabric ready</span>
          )}
          {row.fabric_status === 'consumed' && (
            <span className="rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-medium text-green-700">Fabric cut</span>
          )}
          {row.blockers.length > 0 && (
            <span className="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-600">Needs setup</span>
          )}
        </div>
        <p className="mt-1 text-xs text-[var(--color-text-muted)]">
          {row.order_code ? <span className="ss-mono">{row.order_code}</span> : ''} · {row.customer_name ?? '—'} · {row.product_type}
          {row.delivery_date ? ` · due ${format(new Date(row.delivery_date), 'dd MMM')}` : ''}
        </p>
      </div>
      <ArrowRight size={16} strokeWidth={2} className="text-[var(--color-text-muted)]" />
    </Link>
  )
}
