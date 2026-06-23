'use client'

import { useState, useRef } from 'react'
import { useRouter } from 'next/navigation'
import { Loader2 } from 'lucide-react'
import { cn } from '@/lib/utils'
import { apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { setToken, setStoredUser } from '@/lib/auth/session'
import { useAuthStore } from '@/lib/auth/store'
import { landingRouteForRoles } from '@/lib/auth/landing'
import { useErrorStore } from '@/components/shell/ErrorDrawer'
import type { AuthSession } from '@/lib/api/types'

export default function TwoFactorPage() {
  const router = useRouter()
  const { setUser, setActiveBranch } = useAuthStore()
  const pushError = useErrorStore((s) => s.push)
  const [code, setCode] = useState(['', '', '', '', '', ''])
  const [loading, setLoading] = useState(false)
  const inputs = useRef<(HTMLInputElement | null)[]>([])

  function handleChange(index: number, value: string) {
    const digit = value.replace(/\D/g, '').slice(-1)
    const next = [...code]
    next[index] = digit
    setCode(next)
    if (digit && index < 5) {
      inputs.current[index + 1]?.focus()
    }
  }

  function handleKeyDown(index: number, e: React.KeyboardEvent<HTMLInputElement>) {
    if (e.key === 'Backspace' && !code[index] && index > 0) {
      inputs.current[index - 1]?.focus()
    }
  }

  function handlePaste(e: React.ClipboardEvent) {
    e.preventDefault()
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
    const next = ['', '', '', '', '', '']
    pasted.split('').forEach((d, i) => { next[i] = d })
    setCode(next)
    inputs.current[Math.min(pasted.length, 5)]?.focus()
  }

  async function handleVerify() {
    const otp = code.join('')
    if (otp.length !== 6) return
    setLoading(true)
    try {
      const envelope = await apiMutate<AuthSession>(
        'post',
        ENDPOINTS.auth['2faConfirm'],
        { code: otp },
      )
      const session = envelope.data
      setToken(session.token)
      setStoredUser(session.user)
      setUser(session.user)
      if (session.user.branch) {
        setActiveBranch(session.user.branch)
        if (typeof window !== 'undefined') {
          sessionStorage.setItem('ss_branch_id', String(session.user.branch.id))
        }
      }
      router.replace(landingRouteForRoles(session.user.roles)) // FE-012
    } catch (err: unknown) {
      const apiErr = err as { message?: string; code?: string; request_id?: string }
      pushError({
        message: apiErr?.message ?? '2FA verification failed',
        code: apiErr?.code ?? '2FA_ERROR',
        request_id: apiErr?.request_id ?? '',
      })
      setCode(['', '', '', '', '', ''])
      inputs.current[0]?.focus()
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-[var(--color-bg)] px-4">
      <div className="w-full max-w-sm">
        <div className="flex flex-col items-center mb-8">
          <span
            className="flex items-center justify-center text-white text-lg font-bold rounded-xl mb-3"
            style={{ width: 48, height: 48, background: 'var(--color-brand)' }}
          >
            SS
          </span>
          <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
            Two-factor verification
          </h1>
          <p className="mt-1 text-sm text-[var(--color-text-muted)] text-center">
            Enter the 6-digit code from your authenticator app.
          </p>
        </div>

        <div className="bg-white rounded-2xl border border-[var(--color-border)] shadow-[var(--shadow-sm)] p-6">
          {/* OTP input grid */}
          <div className="flex gap-2 justify-center mb-6" onPaste={handlePaste}>
            {code.map((digit, i) => (
              <input
                key={i}
                ref={(el) => { inputs.current[i] = el }}
                type="text"
                inputMode="numeric"
                maxLength={1}
                value={digit}
                onChange={(e) => handleChange(i, e.target.value)}
                onKeyDown={(e) => handleKeyDown(i, e)}
                className={cn(
                  'w-11 h-12 text-center text-lg font-semibold rounded-lg border',
                  'text-[var(--color-text-primary)]',
                  'focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:border-transparent',
                  'transition-shadow',
                  digit ? 'border-[var(--color-brand)]' : 'border-[var(--color-border-mid)]',
                )}
              />
            ))}
          </div>

          <button
            onClick={handleVerify}
            disabled={loading || code.join('').length !== 6}
            className={cn(
              'w-full h-10 rounded-lg text-sm font-semibold text-white',
              'bg-[var(--color-brand)] hover:bg-[var(--color-brand-dark)]',
              'focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:ring-offset-2',
              'transition-colors disabled:opacity-60 disabled:cursor-not-allowed',
              'flex items-center justify-center gap-2',
            )}
          >
            {loading && <Loader2 size={15} strokeWidth={2} className="animate-spin" />}
            Verify
          </button>

          <button
            onClick={() => router.replace('/login')}
            className="w-full mt-3 text-sm text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] transition-colors"
          >
            Back to login
          </button>
        </div>
      </div>
    </div>
  )
}
