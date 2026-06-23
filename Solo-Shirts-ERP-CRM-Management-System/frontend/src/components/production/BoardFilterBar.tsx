'use client'

import { useEffect, useRef, useState } from 'react'
import { Search, RotateCcw } from 'lucide-react'
import { cn } from '@/lib/utils'
import { useStageSupervisors, type BoardFilters } from '@/lib/api/hooks/useProduction'

interface Props {
  filters: BoardFilters
  onChange: (f: BoardFilters) => void
}

const inputCls =
  'h-9 px-3 rounded-lg border border-[var(--color-border-mid)] text-sm bg-white ' +
  'text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]'

function Chip({ active, onClick, children }: { active: boolean; onClick: () => void; children: React.ReactNode }) {
  return (
    <button
      onClick={onClick}
      className={cn(
        'px-2.5 h-9 rounded-lg text-xs font-medium border transition-colors',
        active
          ? 'bg-[var(--color-brand)] text-white border-[var(--color-brand)]'
          : 'border-[var(--color-border-mid)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]',
      )}
    >
      {children}
    </button>
  )
}

export function BoardFilterBar({ filters, onChange }: Props) {
  const { data: supervisors } = useStageSupervisors()
  const [search, setSearch] = useState(filters.search ?? '')

  // Always read the latest filters in the debounce without re-arming the timer.
  const filtersRef = useRef(filters)
  filtersRef.current = filters

  // Debounce the typed term into the filter set.
  useEffect(() => {
    const t = setTimeout(() => {
      if ((filtersRef.current.search ?? '') !== search) {
        onChange({ ...filtersRef.current, search: search || undefined })
      }
    }, 350)
    return () => clearTimeout(t)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [search])

  const set = (patch: Partial<BoardFilters>) => onChange({ ...filters, ...patch })
  const toggle = (key: 'delayed' | 'rework' | 'ready') => set({ [key]: filters[key] ? undefined : true })

  // Distinct supervisors for the dropdown.
  const supUsers = Array.from(
    new Map((supervisors ?? []).map((s) => [s.user_id, s.user_name ?? `#${s.user_id}`])).entries(),
  )

  const active = Object.values(filters).some((v) => v !== undefined && v !== '' && v !== false)

  return (
    <div className="flex flex-wrap items-center gap-2">
      <div className="relative">
        <Search size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[var(--color-text-muted)]" />
        <input
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Order, customer or item…"
          className={cn(inputCls, 'pl-8 w-52')}
        />
      </div>

      <select value={filters.priority ?? ''} onChange={(e) => set({ priority: e.target.value || undefined })} className={inputCls}>
        <option value="">All priorities</option>
        <option value="urgent">Urgent</option>
        <option value="high">High</option>
        <option value="normal">Normal</option>
      </select>

      {supUsers.length > 0 && (
        <select
          value={filters.supervisor_id ?? ''}
          onChange={(e) => set({ supervisor_id: e.target.value ? Number(e.target.value) : undefined })}
          className={inputCls}
        >
          <option value="">All supervisors</option>
          {supUsers.map(([id, name]) => (
            <option key={id} value={id}>
              {name}
            </option>
          ))}
        </select>
      )}

      <input
        type="date"
        value={filters.date_from ?? ''}
        onChange={(e) => set({ date_from: e.target.value || undefined })}
        className={inputCls}
        title="Delivery date from"
      />
      <input
        type="date"
        value={filters.date_to ?? ''}
        onChange={(e) => set({ date_to: e.target.value || undefined })}
        className={inputCls}
        title="Delivery date to"
      />

      <Chip active={!!filters.delayed} onClick={() => toggle('delayed')}>Delayed</Chip>
      <Chip active={!!filters.rework} onClick={() => toggle('rework')}>Rework</Chip>
      <Chip active={!!filters.ready} onClick={() => toggle('ready')}>Ready</Chip>

      {active && (
        <button
          onClick={() => {
            setSearch('')
            onChange({})
          }}
          className="inline-flex items-center gap-1 px-2.5 h-9 rounded-lg text-xs text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]"
        >
          <RotateCcw size={13} /> Clear
        </button>
      )}
    </div>
  )
}
