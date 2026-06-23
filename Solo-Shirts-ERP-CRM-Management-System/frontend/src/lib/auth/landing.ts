import { ROLES } from '@/lib/auth/permissions'

/**
 * FE-012 — role-specific landing route after login / 2FA.
 *
 * Owner and Admin keep the global dashboard (cross-branch overview). Every other
 * role lands on its primary workspace so it opens straight into the work it owns.
 */
const ROLE_LANDING: Record<string, string> = {
  [ROLES.FRONT_DESK]:  '/front-desk',
  [ROLES.MEASUREMENT]: '/measurements',
  [ROLES.PRODUCTION]:  '/production',
  [ROLES.CUTTER]:      '/cutting',
  [ROLES.TAILOR]:      '/tailoring',
  [ROLES.QC]:          '/qc',
  [ROLES.KAJA]:        '/production',
  [ROLES.IRONING]:     '/production',
  [ROLES.REWORKER]:    '/production',
  [ROLES.INVENTORY]:   '/inventory',
  [ROLES.ACCOUNTANT]:  '/finance',
  [ROLES.DELIVERY]:    '/deliveries',
}

export function landingRouteForRoles(roles: string[] | undefined | null): string {
  if (!roles || roles.length === 0) return '/dashboard'
  // Owner / Admin → global dashboard.
  if (roles.includes(ROLES.OWNER) || roles.includes(ROLES.ADMIN)) return '/dashboard'
  for (const role of roles) {
    if (ROLE_LANDING[role]) return ROLE_LANDING[role]
  }
  return '/dashboard'
}
