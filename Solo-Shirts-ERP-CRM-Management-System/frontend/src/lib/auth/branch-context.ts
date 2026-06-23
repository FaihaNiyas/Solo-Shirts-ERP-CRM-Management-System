'use client'

import { useAuthStore } from '@/lib/auth/store'
import { apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import type { AuthSession, Branch } from '@/lib/api/types'

export function useBranchContext() {
  const { activeBranch, branches, setActiveBranch, setBranches } = useAuthStore()

  async function switchBranch(branchId: number): Promise<void> {
    const envelope = await apiMutate<AuthSession>(
      'post',
      ENDPOINTS.auth.switchBranch,
      { branch_id: branchId },
    )
    const target = branches.find((b) => b.id === branchId)
    if (target) setActiveBranch(target)
    if (typeof window !== 'undefined') {
      sessionStorage.setItem('ss_token', envelope.data.token)
    }
  }

  function initBranches(list: Branch[], defaultBranch: Branch) {
    setBranches(list)
    setActiveBranch(defaultBranch)
  }

  return {
    activeBranch,
    branches,
    switchBranch,
    initBranches,
  }
}
