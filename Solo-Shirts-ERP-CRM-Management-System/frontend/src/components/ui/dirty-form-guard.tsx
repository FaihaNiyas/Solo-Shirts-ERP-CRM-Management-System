'use client'

import { useFormGuard } from '@/lib/hooks/useFormGuard'

interface DirtyFormGuardProps {
  isDirty: boolean
}

export function DirtyFormGuard({ isDirty }: DirtyFormGuardProps) {
  useFormGuard(isDirty)
  return null
}
