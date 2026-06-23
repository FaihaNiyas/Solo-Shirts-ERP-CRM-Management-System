'use client'

import { Store } from 'lucide-react'
import { useAuthStore } from '@/lib/auth/store'

/**
 * Header identity chip for the Front Desk area: User | Role | Branch.
 * Falls back to "Branch: Not selected" when no branch is resolvable.
 */
export function IdentityBar() {
  const user = useAuthStore((s) => s.user)
  const activeBranch = useAuthStore((s) => s.activeBranch)

  const name = user?.name ?? 'User'
  const role = user?.roles?.[0] ?? 'Member'
  const branchName = user?.branch?.name ?? activeBranch?.name ?? null

  return (
    <div className="inline-flex items-center gap-2 rounded-full border border-[var(--color-border)] bg-white px-3 py-1.5 text-xs">
      <Store size={14} strokeWidth={1.75} className="text-[var(--color-brand)] shrink-0" />
      <span className="font-semibold text-[var(--color-text-primary)]">{name}</span>
      <span className="text-[var(--color-border-mid)]" aria-hidden>|</span>
      <span className="text-[var(--color-text-secondary)]">{role}</span>
      <span className="text-[var(--color-border-mid)]" aria-hidden>|</span>
      <span className={branchName ? 'text-[var(--color-text-secondary)]' : 'text-[var(--color-warning)] font-medium'}>
        Branch: {branchName ?? 'Not selected'}
      </span>
    </div>
  )
}
