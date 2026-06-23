'use client'

import { useState } from 'react'
import { AlertTriangle, Copy, Check, RefreshCw } from 'lucide-react'
import { cn } from '@/lib/utils'

interface ErrorStateProps {
  message?: string
  requestId?: string
  onRetry?: () => void
  className?: string
}

export function ErrorState({ message, requestId, onRetry, className }: ErrorStateProps) {
  const [copied, setCopied] = useState(false)

  async function copyRequestId() {
    if (!requestId) return
    await navigator.clipboard.writeText(requestId)
    setCopied(true)
    setTimeout(() => setCopied(false), 1500)
  }

  return (
    <div
      className={cn(
        'flex flex-col items-center justify-center py-12 px-6 text-center',
        className,
      )}
    >
      <div className="flex items-center justify-center w-12 h-12 rounded-2xl bg-red-50 mb-4">
        <AlertTriangle size={24} strokeWidth={1.5} className="text-[var(--color-danger)]" />
      </div>
      <h3 className="text-sm font-semibold text-[var(--color-text-primary)] mb-1">
        Something went wrong
      </h3>
      {message && (
        <p className="text-sm text-[var(--color-text-muted)] max-w-sm">{message}</p>
      )}
      {requestId && (
        <button
          onClick={copyRequestId}
          className="flex items-center gap-1.5 mt-3 text-xs font-mono text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] transition-colors"
        >
          {copied ? <Check size={12} strokeWidth={2} /> : <Copy size={12} strokeWidth={1.5} />}
          req:{requestId.slice(0, 12)}…
        </button>
      )}
      {onRetry && (
        <button
          onClick={onRetry}
          className="flex items-center gap-2 mt-4 px-4 py-2 rounded-lg text-sm font-medium bg-[var(--color-brand)] text-white hover:bg-[var(--color-brand-dark)] transition-colors"
        >
          <RefreshCw size={14} strokeWidth={1.75} />
          Try again
        </button>
      )}
    </div>
  )
}
