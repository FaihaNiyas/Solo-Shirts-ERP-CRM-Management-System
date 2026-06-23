import type { ReactNode } from 'react'

// FE-001: the auth routes (login, 2fa) are client-only, auth-gated pages that
// never need static generation. Next 15.5 fails to prerender them ("module …
// #default not found in the React Client Manifest"). Forcing the segment to
// render dynamically (a server-module route-segment config, which IS honoured)
// skips the broken SSG pass. No UI change — this layout just passes children
// through.
export const dynamic = 'force-dynamic'

export default function AuthLayout({ children }: { children: ReactNode }) {
  return children
}
