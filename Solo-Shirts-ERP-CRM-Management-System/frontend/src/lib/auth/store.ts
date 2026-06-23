'use client'

import { create } from 'zustand'
import type { User, Branch } from '@/lib/api/types'

interface AuthState {
  user: User | null
  activeBranch: Branch | null
  branches: Branch[]
  setUser: (user: User | null) => void
  setActiveBranch: (branch: Branch) => void
  setBranches: (branches: Branch[]) => void
  reset: () => void
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  activeBranch: null,
  branches: [],
  setUser: (user) => set({ user }),
  setActiveBranch: (branch) => {
    if (typeof window !== 'undefined') {
      sessionStorage.setItem('ss_branch_id', String(branch.id))
    }
    set({ activeBranch: branch })
  },
  setBranches: (branches) => set({ branches }),
  reset: () => set({ user: null, activeBranch: null, branches: [] }),
}))
