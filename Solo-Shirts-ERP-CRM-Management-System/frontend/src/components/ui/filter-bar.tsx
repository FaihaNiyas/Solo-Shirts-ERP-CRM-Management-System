'use client'

import { useState } from 'react'
import { SlidersHorizontal, ChevronDown, X } from 'lucide-react'
import { cn } from '@/lib/utils'
import { SearchInput } from './search-input'

interface FilterOption {
  value: string
  label: string
}

interface FilterConfig {
  key: string
  label: string
  options: FilterOption[]
  multiple?: boolean
}

interface FilterBarProps {
  search?: {
    value: string
    onChange: (v: string) => void
    placeholder?: string
    loading?: boolean
  }
  filters?: FilterConfig[]
  values?: Record<string, string | string[]>
  onChange?: (key: string, value: string | string[]) => void
  activeCount?: number
  onClearAll?: () => void
  className?: string
}

export function FilterBar({
  search,
  filters = [],
  values = {},
  onChange,
  onClearAll,
  className,
}: FilterBarProps) {
  const [openFilter, setOpenFilter] = useState<string | null>(null)

  const activeCount = Object.values(values).filter((v) =>
    Array.isArray(v) ? v.length > 0 : Boolean(v),
  ).length

  return (
    <div className={cn('flex items-center gap-2 flex-wrap', className)}>
      {search && (
        <SearchInput
          value={search.value}
          onChange={search.onChange}
          placeholder={search.placeholder}
          loading={search.loading}
          className="w-64"
        />
      )}

      {filters.map((filter) => {
        const current = values[filter.key]
        const isActive = Array.isArray(current) ? current.length > 0 : Boolean(current)

        return (
          <div key={filter.key} className="relative">
            <button
              onClick={() => setOpenFilter(openFilter === filter.key ? null : filter.key)}
              className={cn(
                'flex items-center gap-1.5 px-3 h-9 rounded-lg border text-sm font-medium transition-colors',
                isActive
                  ? 'border-[var(--color-brand)] bg-[var(--color-brand-light)] text-[var(--color-brand-dark)]'
                  : 'border-[var(--color-border-mid)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]',
              )}
            >
              <SlidersHorizontal size={13} strokeWidth={1.75} />
              {filter.label}
              {isActive && Array.isArray(current) && current.length > 0 && (
                <span className="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[var(--color-brand)] text-white text-[10px] font-bold">
                  {current.length}
                </span>
              )}
              <ChevronDown
                size={12}
                strokeWidth={1.75}
                className={cn('transition-transform', openFilter === filter.key && 'rotate-180')}
              />
            </button>

            {openFilter === filter.key && (
              <>
                <div
                  className="fixed inset-0 z-10"
                  onClick={() => setOpenFilter(null)}
                  aria-hidden
                />
                <div className="absolute left-0 top-full mt-1 z-20 min-w-[160px] bg-white rounded-xl border border-[var(--color-border)] shadow-[var(--shadow-md)] py-1">
                  {filter.options.map((opt) => {
                    const selected = Array.isArray(current)
                      ? current.includes(opt.value)
                      : current === opt.value

                    return (
                      <button
                        key={opt.value}
                        onClick={() => {
                          if (filter.multiple) {
                            const arr = Array.isArray(current) ? [...current] : []
                            const next = selected
                              ? arr.filter((v) => v !== opt.value)
                              : [...arr, opt.value]
                            onChange?.(filter.key, next)
                          } else {
                            onChange?.(filter.key, selected ? '' : opt.value)
                            setOpenFilter(null)
                          }
                        }}
                        className={cn(
                          'flex items-center gap-2 w-full px-3 py-2 text-sm text-left transition-colors',
                          selected
                            ? 'bg-[var(--color-brand-light)] text-[var(--color-brand-dark)] font-medium'
                            : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]',
                        )}
                      >
                        {filter.multiple && (
                          <span
                            className={cn(
                              'w-4 h-4 rounded border shrink-0',
                              selected
                                ? 'bg-[var(--color-brand)] border-[var(--color-brand)]'
                                : 'border-[var(--color-border-mid)]',
                            )}
                          />
                        )}
                        {opt.label}
                      </button>
                    )
                  })}
                </div>
              </>
            )}
          </div>
        )
      })}

      {activeCount > 0 && onClearAll && (
        <button
          onClick={onClearAll}
          className="flex items-center gap-1 px-2.5 h-9 rounded-lg text-xs text-[var(--color-text-muted)] hover:text-[var(--color-danger)] hover:bg-red-50 transition-colors"
        >
          <X size={12} strokeWidth={1.75} />
          Clear filters
        </button>
      )}
    </div>
  )
}
