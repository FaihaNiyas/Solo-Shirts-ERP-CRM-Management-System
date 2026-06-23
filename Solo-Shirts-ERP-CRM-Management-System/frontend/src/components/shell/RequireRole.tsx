'use client'

import type { ReactNode } from 'react'
import { usePermission } from '@/lib/auth/permissions'
import { AccessDenied } from '@/components/shell/AccessDenied'

interface RequireRoleProps {
  roles: string | string[]
  children: ReactNode
  message?: string
}

/**
 * FE-011 — route-level permission gate. Renders its children only if the user
 * holds one of the allowed roles; otherwise shows the Access Denied surface
 * (and never mounts the children, so their data queries don't fire). The
 * backend still enforces 403 independently — this is UX only.
 */
export function RequireRole({ roles, children, message }: RequireRoleProps) {
  const { is } = usePermission()
  if (!is(roles)) return <AccessDenied message={message} />
  return <>{children}</>
}
