'use client'

import { useState, useEffect, useCallback, useRef } from 'react'
import { useRouter } from 'next/navigation'
import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { motion, AnimatePresence } from 'framer-motion'
import { Search, X, User, ShoppingBag, FileText } from 'lucide-react'
import { usePreferences } from '@/lib/preferences/PreferencesContext'

interface SearchResult {
  id: number
  type: 'customer' | 'order' | 'invoice'
  title: string
  subtitle?: string
  href: string
}

interface SearchResponse {
  results: SearchResult[]
}

const TYPE_ICON = {
  customer: <User size={14} strokeWidth={1.75} />,
  order: <ShoppingBag size={14} strokeWidth={1.75} />,
  invoice: <FileText size={14} strokeWidth={1.75} />,
}

const TYPE_LABEL = {
  customer: 'Customer',
  order: 'Order',
  invoice: 'Invoice',
}

export function GlobalSearch() {
  const router = useRouter()
  const { preferences } = usePreferences()
  const [open, setOpen] = useState(false)
  const [query, setQuery] = useState('')
  const [debouncedQuery, setDebouncedQuery] = useState('')
  const [activeIdx, setActiveIdx] = useState(0)
  const inputRef = useRef<HTMLInputElement>(null)

  // Ctrl+K open
  useEffect(() => {
    function onKeyDown(e: KeyboardEvent) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault()
        setOpen((v) => !v)
      }
      if (e.key === 'Escape') setOpen(false)
    }
    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [])

  useEffect(() => {
    if (open) setTimeout(() => inputRef.current?.focus(), 50)
    else { setQuery(''); setDebouncedQuery(''); setActiveIdx(0) }
  }, [open])

  // 200ms debounce
  useEffect(() => {
    const t = setTimeout(() => setDebouncedQuery(query), 200)
    return () => clearTimeout(t)
  }, [query])

  const { data, isLoading } = useQuery<SearchResponse>({
    queryKey: ['global-search', debouncedQuery],
    queryFn: () =>
      apiGet<SearchResponse>(ENDPOINTS.search, { q: debouncedQuery, per_page: 12 }).then(
        (r) => r.data,
      ),
    enabled: debouncedQuery.length >= 2,
    staleTime: 30_000,   // cache results 30s — same query re-typed won't re-fetch
    gcTime: 60_000,
  })

  const results = data?.results ?? []

  const navigate = useCallback(
    (result: SearchResult) => {
      router.push(result.href)
      setOpen(false)
    },
    [router],
  )

  function onKeyDown(e: React.KeyboardEvent) {
    if (e.key === 'ArrowDown') {
      e.preventDefault()
      setActiveIdx((i) => Math.min(i + 1, results.length - 1))
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      setActiveIdx((i) => Math.max(i - 1, 0))
    } else if (e.key === 'Enter' && results[activeIdx]) {
      navigate(results[activeIdx])
    }
  }

  const animationEnabled = preferences.animationEnabled !== false

  return (
    <>
      {/* Trigger button in shell header — rendered here as a portal anchor */}
      <AnimatePresence>
        {open && (
          <>
            {/* Backdrop */}
            <motion.div
              initial={animationEnabled ? { opacity: 0 } : {}}
              animate={animationEnabled ? { opacity: 1 } : {}}
              exit={animationEnabled ? { opacity: 0 } : {}}
              transition={{ duration: 0.15 }}
              className="fixed inset-0 bg-black/40 z-40"
              onClick={() => setOpen(false)}
            />

            {/* Panel */}
            <motion.div
              initial={animationEnabled ? { opacity: 0, scale: 0.97, y: -8 } : {}}
              animate={animationEnabled ? { opacity: 1, scale: 1, y: 0 } : {}}
              exit={animationEnabled ? { opacity: 0, scale: 0.97, y: -8 } : {}}
              transition={{ duration: 0.15 }}
              className="fixed top-[10vh] left-1/2 -translate-x-1/2 w-full max-w-xl z-50 rounded-2xl border border-[var(--color-border)] bg-white shadow-2xl overflow-hidden"
            >
              {/* Input */}
              <div className="flex items-center gap-3 px-4 py-3 border-b border-[var(--color-border)]">
                <Search size={16} strokeWidth={1.75} className="text-[var(--color-text-muted)] shrink-0" />
                <input
                  ref={inputRef}
                  type="text"
                  value={query}
                  onChange={(e) => { setQuery(e.target.value); setActiveIdx(0) }}
                  onKeyDown={onKeyDown}
                  placeholder="Search customers, orders, invoices…"
                  className="flex-1 text-sm bg-transparent focus:outline-none text-[var(--color-text-primary)] placeholder:text-[var(--color-text-muted)]"
                />
                {query && (
                  <button onClick={() => setQuery('')} className="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]">
                    <X size={14} />
                  </button>
                )}
                <kbd className="hidden md:inline-flex px-1.5 py-0.5 text-xs text-[var(--color-text-muted)] border border-[var(--color-border)] rounded">
                  Esc
                </kbd>
              </div>

              {/* Results */}
              <div className="max-h-80 overflow-y-auto">
                {debouncedQuery.length < 2 && (
                  <p className="px-4 py-6 text-center text-sm text-[var(--color-text-muted)]">
                    Type at least 2 characters to search
                  </p>
                )}
                {debouncedQuery.length >= 2 && isLoading && (
                  <p className="px-4 py-6 text-center text-sm text-[var(--color-text-muted)]">
                    Searching…
                  </p>
                )}
                {debouncedQuery.length >= 2 && !isLoading && results.length === 0 && (
                  <p className="px-4 py-6 text-center text-sm text-[var(--color-text-muted)]">
                    No results for &ldquo;{debouncedQuery}&rdquo;
                  </p>
                )}
                {results.map((result, idx) => (
                  <button
                    key={`${result.type}-${result.id}`}
                    onClick={() => navigate(result)}
                    onMouseEnter={() => setActiveIdx(idx)}
                    className={`w-full flex items-center gap-3 px-4 py-3 text-left transition-colors ${
                      idx === activeIdx ? 'bg-[var(--color-brand-light)]' : 'hover:bg-[var(--color-surface-alt)]'
                    }`}
                  >
                    <span className={`shrink-0 ${idx === activeIdx ? 'text-[var(--color-brand)]' : 'text-[var(--color-text-muted)]'}`}>
                      {TYPE_ICON[result.type]}
                    </span>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-[var(--color-text-primary)] truncate">
                        {result.title}
                      </p>
                      {result.subtitle && (
                        <p className="text-xs text-[var(--color-text-muted)] truncate">
                          {result.subtitle}
                        </p>
                      )}
                    </div>
                    <span className="shrink-0 text-xs text-[var(--color-text-muted)]">
                      {TYPE_LABEL[result.type]}
                    </span>
                  </button>
                ))}
              </div>

              {/* Footer */}
              <div className="px-4 py-2 border-t border-[var(--color-border)] bg-[var(--color-surface-alt)] flex items-center gap-4 text-xs text-[var(--color-text-muted)]">
                <span><kbd className="font-mono">↑↓</kbd> navigate</span>
                <span><kbd className="font-mono">↵</kbd> open</span>
                <span><kbd className="font-mono">Esc</kbd> close</span>
              </div>
            </motion.div>
          </>
        )}
      </AnimatePresence>
    </>
  )
}

// Trigger button for the shell header — Sixpay-style search pill
export function GlobalSearchTrigger() {
  return (
    <button
      onClick={() => window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true }))}
      className="hidden md:flex items-center gap-2.5 w-full max-w-[440px] h-10 px-3.5 rounded-full bg-[var(--bg-neutral)] text-[var(--color-text-muted)] text-[13.5px] hover:bg-[var(--color-border-mid)]/60 transition-colors"
      aria-label="Search customers, orders, invoices"
    >
      <Search size={16} strokeWidth={1.75} className="shrink-0" />
      <span className="truncate">Search customer, order, invoice, roll…</span>
      <span className="ml-auto hidden lg:flex items-center gap-1 shrink-0">
        <kbd className="font-mono text-[11px] px-1.5 py-0.5 rounded-md bg-white border border-[var(--color-border-mid)] text-[var(--color-text-secondary)]">
          Ctrl
        </kbd>
        <kbd className="font-mono text-[11px] px-1.5 py-0.5 rounded-md bg-white border border-[var(--color-border-mid)] text-[var(--color-text-secondary)]">
          K
        </kbd>
      </span>
    </button>
  )
}
