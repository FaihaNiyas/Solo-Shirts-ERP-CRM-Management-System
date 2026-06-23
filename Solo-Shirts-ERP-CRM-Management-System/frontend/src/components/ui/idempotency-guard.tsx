'use client'

import { useRef, useState } from 'react'
import { Loader2, CheckCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { generateIdempotencyKey } from '@/lib/api/client'

interface IdempotencyGuardProps {
  onAction: (idempotencyKey: string) => Promise<void>
  children: React.ReactNode
  className?: string
  disabled?: boolean
  successDuration?: number
}

export function IdempotencyGuard({
  onAction,
  children,
  className,
  disabled = false,
  successDuration = 1500,
}: IdempotencyGuardProps) {
  const [state, setState] = useState<'idle' | 'loading' | 'success' | 'error'>('idle')
  const keyRef = useRef<string>(generateIdempotencyKey())

  async function handleClick() {
    if (state === 'loading') return
    setState('loading')
    try {
      await onAction(keyRef.current)
      setState('success')
      // Fresh key for next action
      keyRef.current = generateIdempotencyKey()
      setTimeout(() => setState('idle'), successDuration)
    } catch {
      setState('error')
      // Keep same key on error — allows idempotent retry
      setTimeout(() => setState('idle'), 2000)
    }
  }

  return (
    <button
      onClick={handleClick}
      disabled={disabled || state === 'loading'}
      className={cn(
        'inline-flex items-center gap-2 transition-colors',
        'disabled:opacity-60 disabled:cursor-not-allowed',
        className,
      )}
      type="button"
    >
      {state === 'loading' && <Loader2 size={14} strokeWidth={2} className="animate-spin shrink-0" />}
      {state === 'success' && <CheckCircle size={14} strokeWidth={2} className="text-green-500 shrink-0" />}
      {children}
    </button>
  )
}
