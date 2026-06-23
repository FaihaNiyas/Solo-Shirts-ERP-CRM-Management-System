'use client'

import { useEffect } from 'react'
import { AlertTriangle, RefreshCw } from 'lucide-react'
import { cn } from '@/lib/utils'

interface ErrorPageProps {
  error: Error & { digest?: string }
  reset: () => void
}

export default function ShellError({ error, reset }: ErrorPageProps) {
  useEffect(() => {
    console.error(error)
  }, [error])

  return (
    <div className="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
      <div
        className="flex items-center justify-center w-14 h-14 rounded-2xl mb-4"
        style={{ background: '#FEE2E2' }}
      >
        <AlertTriangle size={24} strokeWidth={1.75} className="text-[var(--color-danger)]" />
      </div>

      <h2 className="text-lg font-semibold text-[var(--color-text-primary)] mb-2">
        Something went wrong
      </h2>
      <p className="text-sm text-[var(--color-text-secondary)] mb-6 max-w-sm">
        {error.message ?? 'An unexpected error occurred. Please try again.'}
      </p>

      {error.digest && (
        <p className="text-xs font-mono text-[var(--color-text-muted)] mb-4">
          Error ID: {error.digest}
        </p>
      )}

      <button
        onClick={reset}
        className={cn(
          'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium',
          'bg-[var(--color-brand)] text-white',
          'hover:bg-[var(--color-brand-dark)] transition-colors',
        )}
      >
        <RefreshCw size={14} strokeWidth={1.75} />
        Try again
      </button>
    </div>
  )
}
