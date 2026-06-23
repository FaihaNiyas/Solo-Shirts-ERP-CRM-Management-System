'use client'

import { createContext, useContext, useEffect } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { useAuthStore } from '@/lib/auth/store'
import type { Branch } from '@/lib/api/types'

interface BranchContextValue {
  activeBranch: Branch | null
  branches: Branch[]
  switchBranch: (id: number) => Promise<void>
}

const BranchContext = createContext<BranchContextValue | null>(null)

interface BranchProviderProps {
  children: React.ReactNode
  initialBranches?: Branch[]
  initialActiveBranch?: Branch | null
}

export function BranchProvider({
  children,
  initialBranches = [],
  initialActiveBranch = null,
}: BranchProviderProps) {
  const { activeBranch, branches, setActiveBranch, setBranches } = useAuthStore()
  const queryClient = useQueryClient()

  useEffect(() => {
    if (initialBranches.length > 0) setBranches(initialBranches)
    if (initialActiveBranch) setActiveBranch(initialActiveBranch)
  }, []) // eslint-disable-line react-hooks/exhaustive-deps

  async function switchBranch(id: number): Promise<void> {
    const { apiMutate } = await import('@/lib/api/client')
    const { ENDPOINTS } = await import('@/lib/api/endpoints')
    const envelope = await apiMutate<{ token: string }>('post', ENDPOINTS.auth.switchBranch, {
      branch_id: id,
    })
    const target = branches.find((b) => b.id === id)
    if (target) setActiveBranch(target)
    if (typeof window !== 'undefined') {
      sessionStorage.setItem('ss_token', envelope.data.token)
    }
    // FE-009: every cached query is branch-scoped, so drop the whole cache after
    // switching. Subsequent queries refetch with the new branch's token.
    queryClient.clear()
  }

  return (
    <BranchContext.Provider value={{ activeBranch, branches, switchBranch }}>
      {children}
    </BranchContext.Provider>
  )
}

export function useBranch(): BranchContextValue {
  const ctx = useContext(BranchContext)
  if (!ctx) throw new Error('useBranch must be used within BranchProvider')
  return ctx
}
