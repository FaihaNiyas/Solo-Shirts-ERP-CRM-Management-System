'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Eye, EyeOff, Loader2, AlertCircle, Zap } from 'lucide-react'
import { cn } from '@/lib/utils'
import { apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { setToken, setStoredUser } from '@/lib/auth/session'
import { useAuthStore } from '@/lib/auth/store'
import { landingRouteForRoles } from '@/lib/auth/landing'
import type { AuthSession } from '@/lib/api/types'

const schema = z.object({
  email: z.string().email('Enter a valid email'),
  password: z.string().min(6, 'Password must be at least 6 characters'),
})

type FormValues = z.infer<typeof schema>

const DEMO_USERS = [
  { label: 'Owner',       email: 'owner@soloshirts.test',      bg: '#92400E', text: '#FEF3C7', desc: 'Full access' },
  { label: 'Front Desk',  email: 'frontdesk@soloshirts.test',  bg: '#1D4ED8', text: '#EFF6FF', desc: 'Orders & customers' },
  { label: 'Supervisor',  email: 'supervisor@soloshirts.test', bg: '#6D28D9', text: '#EDE9FE', desc: 'Production overview' },
  { label: 'Cutter',      email: 'cutter@soloshirts.test',     bg: '#B45309', text: '#FFFBEB', desc: 'Cutting queue' },
  { label: 'Tailor',      email: 'tailor1@soloshirts.test',    bg: '#065F46', text: '#ECFDF5', desc: 'Tailoring tasks' },
  { label: 'QC',          email: 'qc@soloshirts.test',         bg: '#991B1B', text: '#FEF2F2', desc: 'Quality control' },
  { label: 'Inventory',   email: 'inventory@soloshirts.test',  bg: '#0C4A6E', text: '#F0F9FF', desc: 'Fabric & stock' },
  { label: 'Accountant',  email: 'accountant@soloshirts.test', bg: '#374151', text: '#F9FAFB', desc: 'Finance & invoices' },
  { label: 'Delivery',    email: 'delivery@soloshirts.test',   bg: '#78350F', text: '#FFFBEB', desc: 'Deliveries & OTP' },
]

export default function LoginPage() {
  const router = useRouter()
  const { setUser, setBranches, setActiveBranch } = useAuthStore()
  const [showPassword, setShowPassword] = useState(false)
  const [loading, setLoading] = useState(false)
  const [quickLoading, setQuickLoading] = useState<string | null>(null)
  const [errorMsg, setErrorMsg] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  async function performLogin(email: string, password: string) {
    const envelope = await apiMutate<{
      token: string
      token_type: string
      user: import('@/lib/api/types').User
      abilities: string[]
      requires_2fa?: boolean
    }>('post', ENDPOINTS.auth.login, { email, password })
    const session = envelope.data

    if (session.requires_2fa) {
      setToken(session.token)
      router.push('/2fa')
      return
    }

    const user = { ...session.user, permissions: session.abilities ?? [] }
    setToken(session.token)
    setStoredUser(user)
    setUser(user)

    if (user.branch) {
      setActiveBranch(user.branch)
      if (typeof window !== 'undefined') {
        sessionStorage.setItem('ss_branch_id', String(user.branch.id))
      }
    }

    router.replace(landingRouteForRoles(user.roles)) // FE-012: role-specific landing
  }

  async function onSubmit(values: FormValues) {
    setLoading(true)
    setErrorMsg(null)
    try {
      await performLogin(values.email, values.password)
    } catch (err: unknown) {
      const apiErr = err as { message?: string; code?: string }
      setErrorMsg(apiErr?.message ?? 'Login failed. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  async function quickLogin(email: string) {
    setQuickLoading(email)
    setErrorMsg(null)
    setValue('email', email)
    setValue('password', 'password')
    try {
      await performLogin(email, 'password')
    } catch (err: unknown) {
      const apiErr = err as { message?: string; code?: string }
      setErrorMsg(apiErr?.message ?? 'Login failed. Please try again.')
    } finally {
      setQuickLoading(null)
    }
  }

  return (
    <div
      className="min-h-screen flex items-center justify-center px-4 py-10"
      style={{
        background:
          'radial-gradient(120% 120% at 100% 0%, #FEF3C7 0%, rgba(254,243,199,0) 46%), var(--color-bg)',
      }}
    >
      <div className="w-full max-w-sm ss-fade-up">
        {/* Brand */}
        <div className="flex flex-col items-center mb-8">
          <span
            className="flex items-center justify-center text-white font-bold rounded-2xl mb-4 shadow-[var(--shadow-md)]"
            style={{ width: 52, height: 52, background: 'var(--color-brand)', fontSize: 19, letterSpacing: '-0.02em' }}
          >
            SS
          </span>
          <h1 className="text-2xl font-bold tracking-tight text-[var(--color-text-primary)]">
            Solo Shirts <span className="text-[var(--color-brand-dark)]">ERP</span>
          </h1>
          <p className="mt-1.5 text-sm text-[var(--color-text-secondary)]">
            Precision tailoring, managed.
          </p>
        </div>

        {/* Form card */}
        <div className="bg-white rounded-2xl border border-[var(--color-border-mid)] shadow-[var(--shadow-md)] p-6">
          {errorMsg && (
            <div className="flex items-start gap-2 mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
              <AlertCircle size={15} className="mt-0.5 shrink-0" />
              <span>{errorMsg}</span>
            </div>
          )}
          <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
            {/* Email */}
            <div>
              <label
                htmlFor="email"
                className="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5"
              >
                Email
              </label>
              <input
                {...register('email')}
                id="email"
                type="email"
                autoComplete="email"
                placeholder="you@soloshirts.in"
                // Email-field browser extensions (e.g. Temp Mail) inject attributes
                // into this input before hydration; suppress the benign mismatch.
                suppressHydrationWarning
                className={cn(
                  'w-full h-10 px-3 rounded-lg border text-sm',
                  'text-[var(--color-text-primary)] bg-white',
                  'focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:border-transparent',
                  'transition-shadow',
                  errors.email
                    ? 'border-[var(--color-danger)]'
                    : 'border-[var(--color-border-mid)]',
                )}
              />
              {errors.email && (
                <p className="mt-1 text-xs text-[var(--color-danger)]">{errors.email.message}</p>
              )}
            </div>

            {/* Password */}
            <div>
              <label
                htmlFor="password"
                className="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5"
              >
                Password
              </label>
              <div className="relative">
                <input
                  {...register('password')}
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  autoComplete="current-password"
                  placeholder="••••••••"
                  // Password managers inject attributes pre-hydration — same benign mismatch.
                  suppressHydrationWarning
                  className={cn(
                    'w-full h-10 px-3 pr-10 rounded-lg border text-sm',
                    'text-[var(--color-text-primary)] bg-white',
                    'focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:border-transparent',
                    'transition-shadow',
                    errors.password
                      ? 'border-[var(--color-danger)]'
                      : 'border-[var(--color-border-mid)]',
                  )}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((v) => !v)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] transition-colors"
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                >
                  {showPassword ? (
                    <EyeOff size={16} strokeWidth={1.75} />
                  ) : (
                    <Eye size={16} strokeWidth={1.75} />
                  )}
                </button>
              </div>
              {errors.password && (
                <p className="mt-1 text-xs text-[var(--color-danger)]">{errors.password.message}</p>
              )}
            </div>

            {/* Submit */}
            <button
              type="submit"
              disabled={loading || !!quickLoading}
              className={cn(
                'w-full h-10 rounded-lg text-sm font-semibold text-white',
                'bg-[var(--color-brand)] hover:bg-[var(--color-brand-dark)]',
                'focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:ring-offset-2',
                'transition-colors disabled:opacity-60 disabled:cursor-not-allowed',
                'flex items-center justify-center gap-2 mt-2',
              )}
            >
              {loading && <Loader2 size={15} strokeWidth={2} className="animate-spin" />}
              Sign in
            </button>
          </form>
        </div>

        {/* Demo quick-login */}
        <div className="mt-5">
          <div className="flex items-center gap-2 mb-3">
            <div className="flex-1 h-px bg-[var(--color-border-soft)]" />
            <span className="flex items-center gap-1.5 text-xs font-medium text-[var(--color-text-muted)] px-1">
              <Zap size={11} />
              Demo — quick login
            </span>
            <div className="flex-1 h-px bg-[var(--color-border-soft)]" />
          </div>

          <div className="grid grid-cols-3 gap-2">
            {DEMO_USERS.map((u) => {
              const busy = quickLoading === u.email
              return (
                <button
                  key={u.email}
                  type="button"
                  disabled={loading || !!quickLoading}
                  onClick={() => quickLogin(u.email)}
                  className={cn(
                    'relative flex flex-col items-start px-3 py-2.5 rounded-xl border text-left',
                    'transition-all duration-150',
                    'disabled:opacity-50 disabled:cursor-not-allowed',
                    'hover:scale-[1.03] active:scale-[0.98]',
                    'shadow-sm hover:shadow-md',
                  )}
                  style={{
                    background: u.bg,
                    borderColor: 'transparent',
                  }}
                >
                  {busy && (
                    <span className="absolute inset-0 flex items-center justify-center rounded-xl bg-black/20">
                      <Loader2 size={14} className="animate-spin text-white" />
                    </span>
                  )}
                  <span
                    className="text-xs font-semibold leading-tight"
                    style={{ color: u.text }}
                  >
                    {u.label}
                  </span>
                  <span
                    className="text-[10px] leading-tight mt-0.5 opacity-75"
                    style={{ color: u.text }}
                  >
                    {u.desc}
                  </span>
                </button>
              )
            })}
          </div>

          <p className="text-center text-[10px] text-[var(--color-text-muted)] mt-3">
            All demo accounts use password: <code className="font-mono">password</code>
          </p>
        </div>
      </div>
    </div>
  )
}
