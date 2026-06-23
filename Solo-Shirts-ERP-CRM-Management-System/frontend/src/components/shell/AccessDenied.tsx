'use client'

import Link from 'next/link'
import { ShieldX } from 'lucide-react'

interface AccessDeniedProps {
  message?: string
}

/**
 * FE-011 — friendly 403 / Access Denied surface. Shown when a user reaches a
 * route or action they lack permission for. The backend remains the security
 * enforcer (it still returns 403); this is the UX layer.
 */
export function AccessDenied({ message }: AccessDeniedProps) {
  return (
    <div className="flex flex-col items-center justify-center py-16 px-6 text-center">
      <div className="flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-50 mb-4">
        <ShieldX size={26} strokeWidth={1.5} className="text-[var(--color-brand-dark)]" />
      </div>
      <h2 className="text-base font-semibold text-[var(--color-text-primary)] mb-1">Access denied</h2>
      <p className="text-sm text-[var(--color-text-muted)] max-w-sm">
        {message ?? 'You do not have permission to view this page. If you think this is a mistake, contact your administrator.'}
      </p>
      <Link
        href="/dashboard"
        className="mt-5 px-4 py-2 rounded-lg text-sm font-medium bg-[var(--color-brand)] text-white hover:bg-[var(--color-brand-dark)] transition-colors"
      >
        Back to dashboard
      </Link>
    </div>
  )
}
