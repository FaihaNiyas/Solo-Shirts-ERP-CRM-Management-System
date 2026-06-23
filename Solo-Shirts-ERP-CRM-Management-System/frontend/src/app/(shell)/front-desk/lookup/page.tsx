'use client'

import { useState } from 'react'
import Link from 'next/link'
import { AlertTriangle, Loader2, PackageSearch, SearchX } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { SearchInput } from '@/components/ui/search-input'
import { useOrderLookup } from '@/lib/api/hooks/useFrontDeskLookup'
import { OrderLookupCard } from '@/components/front-desk/lookup/OrderLookupCard'

export default function OrderLookupPage() {
  const [q, setQ] = useState('')
  const { data, isFetching, isError, error } = useOrderLookup(q)
  const err = error as { message?: string; request_id?: string } | null
  const ready = q.trim().length >= 2

  return (
    <div className="space-y-5">
      <PageHeader
        title="Order Status Lookup"
        description="Search by phone, order code, or sub-order code"
        actions={
          <Link
            href="/front-desk/ready-rack"
            className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-10 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
          >
            <PackageSearch size={15} strokeWidth={1.75} /> Ready Rack Search
          </Link>
        }
      />

      <div className="max-w-xl">
        <SearchInput value={q} onChange={setQ} placeholder="Phone / ORD-… / sub-order code" autoFocus />
      </div>

      {!ready && (
        <p className="py-12 text-center text-sm text-[var(--color-text-muted)]">
          Type at least 2 characters to search.
        </p>
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
          {data.map((order) => (
            <OrderLookupCard key={order.id} order={order} />
          ))}
        </div>
      )}
    </div>
  )
}
