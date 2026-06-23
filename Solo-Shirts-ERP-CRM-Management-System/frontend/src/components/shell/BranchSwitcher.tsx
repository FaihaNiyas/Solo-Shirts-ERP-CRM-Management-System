'use client'

import { Building2 } from 'lucide-react'
import { useAuthStore } from '@/lib/auth/store'

/**
 * Shows the branch assigned to the logged-in user at account creation.
 * Plain display — no switching.
 */
export function BranchSwitcher() {
  const user = useAuthStore((s) => s.user)
  const branchName = user?.branch?.name

  if (!branchName) return null

  return (
    <div className="flex items-center gap-1.5 px-2 py-1 rounded-md text-sm text-[var(--color-text-secondary)]">
      <Building2 size={14} strokeWidth={1.75} className="text-[var(--color-brand)]" />
      <span className="font-medium truncate max-w-[180px]">Branch: {branchName}</span>
    </div>
  )
}
