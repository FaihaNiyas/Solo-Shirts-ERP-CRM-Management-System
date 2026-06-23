'use client'

import { useAuthStore } from '@/lib/auth/store'

export function useAuth() {
  const user = useAuthStore((s) => s.user)
  const activeBranch = useAuthStore((s) => s.activeBranch)
  return { user, activeBranch }
}
