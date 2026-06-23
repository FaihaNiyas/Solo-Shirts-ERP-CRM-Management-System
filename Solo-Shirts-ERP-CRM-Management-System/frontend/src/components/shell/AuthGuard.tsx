'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { Loader2 } from 'lucide-react'
import { useAuthStore } from '@/lib/auth/store'
import { isAuthenticated, clearSession, getStoredUser } from '@/lib/auth/session'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import type { User } from '@/lib/api/types'

export function AuthGuard({ children }: { children: React.ReactNode }) {
  const router = useRouter()
  const setUser = useAuthStore((s) => s.setUser)
  const setActiveBranch = useAuthStore((s) => s.setActiveBranch)

  // Must start false on both server and first client render — reading
  // sessionStorage here would diverge (null on server, user on client) and
  // trigger a hydration mismatch that forces React to rebuild the whole tree.
  // The effect below reads storage after mount and flips ready synchronously.
  const [ready, setReady] = useState(false)

  useEffect(() => {
    // On hard refresh Zustand resets to null; getStoredUser() reads from
    // sessionStorage (synchronous) so children mount without a network round-trip.
    let currentUser = useAuthStore.getState().user
    if (!currentUser) {
      const stored = getStoredUser<User>()
      if (stored) {
        useAuthStore.setState({ user: stored })
        if ((stored as User & { branch?: unknown }).branch) {
          useAuthStore.setState({ activeBranch: (stored as User & { branch: unknown }).branch as never })
        }
        currentUser = stored
      }
    }

    if (currentUser) {
      // Render instantly from the cached user, but also re-fetch /me in the
      // background so role/permission changes propagate on reload (not only on a
      // fresh login). A transient failure keeps the cached session untouched.
      setReady(true)
      apiGet<{ user: User; abilities: string[] }>(ENDPOINTS.auth.me)
        .then((res) => {
          const hydrated = { ...res.data.user, permissions: res.data.abilities ?? [] }
          setUser(hydrated)
          if (hydrated.branch) setActiveBranch(hydrated.branch)
        })
        .catch(() => {
          /* keep the cached session; the api client handles a real 401 globally */
        })
      return
    }

    if (!isAuthenticated()) {
      router.replace('/login')
      return
    }

    // Token valid but no cached user object — fetch from API (rare: cleared storage)
    apiGet<{ user: User; abilities: string[] }>(ENDPOINTS.auth.me)
      .then((res) => {
        const hydrated = { ...res.data.user, permissions: res.data.abilities ?? [] }
        setUser(hydrated)
        if (hydrated.branch) setActiveBranch(hydrated.branch)
        setReady(true)
      })
      .catch(() => {
        clearSession()
        router.replace('/login')
      })
  }, []) // eslint-disable-line react-hooks/exhaustive-deps

  if (!ready) {
    return (
      <div className="flex h-screen items-center justify-center bg-[var(--color-bg)]">
        <div className="flex flex-col items-center gap-3">
          <span
            className="flex items-center justify-center text-white text-sm font-bold rounded-xl"
            style={{ width: 40, height: 40, background: 'var(--color-brand)' }}
          >
            SS
          </span>
          <Loader2 size={20} strokeWidth={1.75} className="animate-spin text-[var(--color-brand)]" />
        </div>
      </div>
    )
  }

  return <>{children}</>
}
