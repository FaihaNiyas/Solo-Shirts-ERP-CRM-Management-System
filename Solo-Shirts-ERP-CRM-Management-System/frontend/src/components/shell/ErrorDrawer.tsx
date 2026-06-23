'use client'

import { useEffect } from 'react'
import { create } from 'zustand'
import { X, AlertTriangle, Copy, Check } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'
import type { ApiError } from '@/lib/api/types'

// Global error store — any component can push an error
interface ErrorStore {
  errors: (ApiError & { id: string })[]
  push: (error: ApiError) => void
  dismiss: (id: string) => void
  clear: () => void
}

export const useErrorStore = create<ErrorStore>((set) => ({
  errors: [],
  push: (error) =>
    set((s) => ({
      errors: [
        ...s.errors,
        { ...error, id: crypto.randomUUID() },
      ].slice(-5), // keep last 5
    })),
  dismiss: (id) =>
    set((s) => ({ errors: s.errors.filter((e) => e.id !== id) })),
  clear: () => set({ errors: [] }),
}))

function CopyButton({ text }: { text: string }) {
  const [copied, setCopied] = useState(false)
  async function copy() {
    await navigator.clipboard.writeText(text)
    setCopied(true)
    setTimeout(() => setCopied(false), 1500)
  }
  return (
    <button
      onClick={copy}
      className="text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] transition-colors"
      title="Copy request ID"
    >
      {copied ? <Check size={12} strokeWidth={2} /> : <Copy size={12} strokeWidth={1.75} />}
    </button>
  )
}

export function ErrorDrawer() {
  const { errors, dismiss } = useErrorStore()

  if (errors.length === 0) return null

  return (
    <div
      className="fixed bottom-4 right-4 z-50 space-y-2 w-80 sm:w-96"
      aria-live="assertive"
      aria-label="Error notifications"
    >
      {errors.map((err) => (
        <div
          key={err.id}
          className={cn(
            'bg-white rounded-xl border-l-4 border-[var(--color-danger)]',
            'shadow-[var(--shadow-lg)] p-4 pr-10 relative',
          )}
          role="alert"
        >
          <button
            onClick={() => dismiss(err.id)}
            className="absolute top-3 right-3 text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] transition-colors"
            aria-label="Dismiss error"
          >
            <X size={14} strokeWidth={1.75} />
          </button>

          <div className="flex items-start gap-2">
            <AlertTriangle
              size={16}
              strokeWidth={1.75}
              className="shrink-0 mt-0.5 text-[var(--color-danger)]"
            />
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-[var(--color-text-primary)] leading-snug">
                {err.message}
              </p>

              {err.errors && (
                <ul className="mt-1.5 space-y-0.5">
                  {Object.entries(err.errors).map(([field, msgs]) => (
                    <li key={field} className="text-xs text-[var(--color-text-secondary)]">
                      <span className="font-medium">{field}:</span> {msgs.join(', ')}
                    </li>
                  ))}
                </ul>
              )}

              {err.request_id && (
                <div className="flex items-center gap-1.5 mt-2">
                  <span className="text-[10px] font-mono text-[var(--color-text-muted)] tracking-wide">
                    req:{err.request_id.slice(0, 8)}…
                  </span>
                  <CopyButton text={err.request_id} />
                </div>
              )}
            </div>
          </div>
        </div>
      ))}
    </div>
  )
}
