'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { useQueryClient } from '@tanstack/react-query'
import { User, LogOut, Settings, ChevronDown } from 'lucide-react'
import { cn } from '@/lib/utils'
import { useAuthStore } from '@/lib/auth/store'
import { apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { clearSession } from '@/lib/auth/session'

export function UserMenu() {
  const [open, setOpen] = useState(false)
  const router = useRouter()
  const queryClient = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const reset = useAuthStore((s) => s.reset)

  async function handleLogout() {
    try {
      await apiMutate('post', ENDPOINTS.auth.logout)
    } catch {
      // ignore — clear session regardless
    } finally {
      clearSession()
      reset()
      // FE-010: drop all cached queries so the next user never sees prior data.
      queryClient.clear()
      router.replace('/login')
    }
  }

  const initials = user?.name
    ? user.name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
    : 'U'

  return (
    <div className="relative">
      <button
        onClick={() => setOpen((v) => !v)}
        className="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-[var(--color-surface-alt)] transition-colors"
        aria-haspopup="menu"
        aria-expanded={open}
      >
        <span
          className="flex items-center justify-center w-7 h-7 rounded-full text-white text-xs font-semibold shrink-0"
          style={{ background: 'var(--color-brand)' }}
        >
          {initials}
        </span>
        <span className="hidden sm:block text-sm font-medium text-[var(--color-text-primary)] max-w-[100px] truncate">
          {user?.name ?? 'User'}
        </span>
        <ChevronDown
          size={14}
          strokeWidth={1.75}
          className={cn(
            'text-[var(--color-text-secondary)] transition-transform',
            open && 'rotate-180',
          )}
        />
      </button>

      {open && (
        <>
          <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} aria-hidden />
          <div
            role="menu"
            className={cn(
              'absolute right-0 top-full mt-1 z-20 w-52',
              'bg-white rounded-xl border border-[var(--color-border)] shadow-[var(--shadow-md)]',
              'py-1 overflow-hidden',
            )}
          >
            {/* Identity */}
            <div className="px-3 py-2.5 border-b border-[var(--color-border)]">
              <p className="text-sm font-medium text-[var(--color-text-primary)] truncate">
                {user?.name}
              </p>
              <p className="text-xs text-[var(--color-text-muted)] truncate mt-0.5">
                {user?.email}
              </p>
            </div>

            <button
              role="menuitem"
              onClick={() => { setOpen(false); router.push('/settings/profile') }}
              className="flex items-center gap-2 w-full px-3 py-2 text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              <User size={15} strokeWidth={1.75} />
              Profile
            </button>
            <button
              role="menuitem"
              onClick={() => { setOpen(false); router.push('/settings') }}
              className="flex items-center gap-2 w-full px-3 py-2 text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              <Settings size={15} strokeWidth={1.75} />
              Settings
            </button>

            <div className="border-t border-[var(--color-border)] mt-1 pt-1">
              <button
                role="menuitem"
                onClick={handleLogout}
                className="flex items-center gap-2 w-full px-3 py-2 text-sm text-[var(--color-danger)] hover:bg-red-50 transition-colors"
              >
                <LogOut size={15} strokeWidth={1.75} />
                Sign out
              </button>
            </div>
          </div>
        </>
      )}
    </div>
  )
}
