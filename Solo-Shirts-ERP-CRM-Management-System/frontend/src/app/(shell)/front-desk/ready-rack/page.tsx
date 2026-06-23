'use client'

import { Suspense, useEffect, useState } from 'react'
import { useSearchParams } from 'next/navigation'
import { AlertTriangle, Loader2, SearchX } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { SearchInput } from '@/components/ui/search-input'
import { useRackSearch } from '@/lib/api/hooks/useFrontDeskLookup'
import { RackResultCard } from '@/components/front-desk/lookup/RackResultCard'

function ReadyRackSearch() {
  const sp = useSearchParams()
  const [q, setQ] = useState('')

  // Pre-fill from a "Search Ready Rack" deep link.
  useEffect(() => {
    const initial = sp.get('q')
    if (initial) setQ(initial)
  }, [sp])

  const { data, isFetching, isError, error } = useRackSearch(q)
  const err = error as { message?: string; request_id?: string } | null
  const ready = q.trim().length >= 2

  return (
    <div className="space-y-5">
      <PageHeader title="Ready Rack Search" description="Find finished orders ready for pickup" />

      <div className="max-w-xl">
        <SearchInput value={q} onChange={setQ} placeholder="Phone / ORD-… / sub-order code" autoFocus />
      </div>

      {!ready && (
        <p className="py-12 text-center text-sm text-[var(--color-text-muted)]">Type at least 2 characters to search.</p>
      )}

      {ready && isFetching && (
        <div className="flex items-center justify-center gap-2 py-12 text-sm text-[var(--color-text-muted)]">
          <Loader2 size={16} className="animate-spin" /> Searching…
        </div>
      )}

      {ready && isError && (
        <div className="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          <AlertTriangle size={16} strokeWidth={1.75} className="mt-0.5 shrink-0" />
          <div>
            <p>{err?.message ?? 'Search failed.'}</p>
            {err?.request_id && <p className="text-xs opacity-75">request_id: {err.request_id}</p>}
          </div>
        </div>
      )}

      {ready && !isFetching && !isError && data && data.length === 0 && (
        <div className="flex flex-col items-center gap-2 py-12 text-center text-sm text-[var(--color-text-muted)]">
          <SearchX size={26} strokeWidth={1.5} />
          No orders match &ldquo;{q}&rdquo;.
        </div>
      )}

      {ready && !isFetching && !isError && data && data.length > 0 && (
        <div className="space-y-3">
          {data.map((r) => (
            <RackResultCard key={r.order_id} result={r} />
          ))}
        </div>
      )}
    </div>
  )
}

export default function ReadyRackPage() {
  return (
    <Suspense fallback={<p className="py-12 text-center text-sm text-[var(--color-text-muted)]">Loading…</p>}>
      <ReadyRackSearch />
    </Suspense>
  )
}
