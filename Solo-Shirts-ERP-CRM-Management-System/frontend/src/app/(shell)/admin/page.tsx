'use client'

import Link from 'next/link'
import { Users, Building2, ChevronRight, Shield, ShieldCheck, KeyRound } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { usePermission, ROLES } from '@/lib/auth/permissions'

/**
 * Admin hub — fixes the `/admin` 404 (the sidebar links here, but the only real
 * pages live at /admin/users and /admin/branches). Cards are gated so Admin sees
 * User Management (`users.view`) and only Owner sees Branches (Owner-only on the
 * backend). Backend still enforces every action.
 */
interface AdminCard {
  label: string
  description: string
  href: string
  icon: React.ElementType
  show: boolean
}

export default function AdminHubPage() {
  const { can, is } = usePermission()

  const cards: AdminCard[] = [
    {
      label: 'User Management',
      description: 'Create users, assign roles, activate or deactivate accounts.',
      href: '/admin/users',
      icon: Users,
      show: can('users.view'),
    },
    {
      label: 'Roles',
      description: 'Create roles and choose which permissions each one grants.',
      href: '/admin/roles',
      icon: ShieldCheck,
      show: can('roles.view'),
    },
    {
      label: 'Permissions',
      description: 'Manage the permission catalogue used across all roles.',
      href: '/admin/permissions',
      icon: KeyRound,
      show: can('permissions.view'),
    },
    {
      label: 'Branches',
      description: 'Create and edit branches. Owner only.',
      href: '/admin/branches',
      icon: Building2,
      show: is(ROLES.OWNER),
    },
  ]

  const visible = cards.filter((c) => c.show)

  if (visible.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center">
        <Shield size={40} strokeWidth={1.25} className="text-[var(--color-text-muted)]" />
        <p className="text-sm text-[var(--color-text-muted)]">Owner or Admin access required</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Admin" description="Manage users, roles, and branches." />

      <div className="grid gap-4 sm:grid-cols-2 max-w-3xl">
        {visible.map((c) => (
          <Link
            key={c.href}
            href={c.href}
            className="group flex items-start gap-4 rounded-xl border border-[var(--color-border)] bg-white p-5 hover:border-[var(--color-brand)] hover:shadow-sm transition-colors"
          >
            <span
              className="flex items-center justify-center rounded-lg shrink-0"
              style={{ width: 40, height: 40, background: 'var(--color-brand-light)', color: 'var(--color-brand)' }}
            >
              <c.icon size={20} strokeWidth={1.75} />
            </span>
            <span className="min-w-0 flex-1">
              <span className="flex items-center gap-1 text-[15px] font-semibold text-[var(--color-text-primary)]">
                {c.label}
                <ChevronRight
                  size={16}
                  strokeWidth={2}
                  className="text-[var(--color-text-muted)] group-hover:text-[var(--color-brand)] transition-colors"
                />
              </span>
              <span className="block mt-1 text-[13px] text-[var(--color-text-muted)]">{c.description}</span>
            </span>
          </Link>
        ))}
      </div>
    </div>
  )
}
