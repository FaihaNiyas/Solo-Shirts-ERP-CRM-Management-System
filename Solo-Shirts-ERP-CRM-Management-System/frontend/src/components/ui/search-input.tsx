'use client'

import { useState, useEffect, useCallback, useRef } from 'react'
import { Search, X, Loader2 } from 'lucide-react'
import { cn } from '@/lib/utils'

interface SearchInputProps {
  value?: string
  onChange: (value: string) => void
  placeholder?: string
  debounce?: number
  loading?: boolean
  className?: string
  autoFocus?: boolean
}

export function SearchInput({
  value = '',
  onChange,
  placeholder = 'Search…',
  debounce = 300,
  loading = false,
  className,
  autoFocus = false,
}: SearchInputProps) {
  const [local, setLocal] = useState(value)
  const timer = useRef<ReturnType<typeof setTimeout>>(undefined)

  useEffect(() => {
    setLocal(value)
  }, [value])

  const handleChange = useCallback(
    (val: string) => {
      setLocal(val)
      clearTimeout(timer.current)
      timer.current = setTimeout(() => onChange(val), debounce)
    },
    [onChange, debounce],
  )

  function clear() {
    setLocal('')
    clearTimeout(timer.current)
    onChange('')
  }

  return (
    <div className={cn('relative', className)}>
      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-text-muted)]">
        {loading ? (
          <Loader2 size={16} strokeWidth={1.75} className="animate-spin" />
        ) : (
          <Search size={16} strokeWidth={1.75} />
        )}
      </span>
      <input
        type="text"
        value={local}
        onChange={(e) => handleChange(e.target.value)}
        placeholder={placeholder}
        autoFocus={autoFocus}
        className={cn(
          'w-full h-9 pl-9 pr-8 rounded-lg border border-[var(--color-border-mid)] text-sm',
          'text-[var(--color-text-primary)] bg-white',
          'focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:border-transparent',
          'placeholder:text-[var(--color-text-muted)]',
        )}
      />
      {local && (
        <button
          onClick={clear}
          className="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] transition-colors"
          aria-label="Clear search"
        >
          <X size={14} strokeWidth={1.75} />
        </button>
      )}
    </div>
  )
}
