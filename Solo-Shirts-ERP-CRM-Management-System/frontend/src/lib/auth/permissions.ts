'use client'

import { useCallback } from 'react'
import { useAuthStore } from '@/lib/auth/store'

// --- Pure helpers (work with any User-like object) ---

export function hasPermission(
  permissions: string[],
  permission: string,
): boolean {
  return permissions.includes(permission)
}

export function hasRole(roles: string[], role: string | string[]): boolean {
  const list = Array.isArray(role) ? role : [role]
  return list.some((r) => roles.includes(r))
}

export function hasAnyPermission(
  permissions: string[],
  list: string[],
): boolean {
  return list.some((p) => permissions.includes(p))
}

export function hasAllPermissions(
  permissions: string[],
  list: string[],
): boolean {
  return list.every((p) => permissions.includes(p))
}

// --- React hook ---

export function usePermission() {
  const user = useAuthStore((s) => s.user)

  const can = useCallback(
    (permission: string) => {
      if (!user) return false
      // Owner token carries ['*'] wildcard — grants all permissions
      if (user.permissions.includes('*')) return true
      return hasPermission(user.permissions, permission)
    },
    [user],
  )

  const is = useCallback(
    (role: string | string[]) => {
      if (!user) return false
      return hasRole(user.roles, role)
    },
    [user],
  )

  const canAny = useCallback(
    (list: string[]) => {
      if (!user) return false
      if (user.permissions.includes('*')) return true
      return hasAnyPermission(user.permissions, list)
    },
    [user],
  )

  return { can, is, canAny, user }
}

// Role constants — values must match exact role names in the database.
// All 14 seeded roles are represented (FE-013).
export const ROLES = {
  OWNER:      'Owner',
  ADMIN:      'Admin',
  TAILOR:     'Tailor',
  CUTTER:     'Cutting Master',
  QC:         'QC Supervisor',
  DELIVERY:   'Delivery Staff',
  ACCOUNTANT: 'Accountant',
  INVENTORY:  'Inventory Manager',
  FRONT_DESK: 'Front Desk',
  MEASUREMENT:'Measurement Staff',
  PRODUCTION: 'Production Supervisor',
  KAJA:       'Kaja Button',
  IRONING:    'Ironing Master',
  REWORKER:   'Re-Worker',
} as const

export type RoleName = (typeof ROLES)[keyof typeof ROLES]
