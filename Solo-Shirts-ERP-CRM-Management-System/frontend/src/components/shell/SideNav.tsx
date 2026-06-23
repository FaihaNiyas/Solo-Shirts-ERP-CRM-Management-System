'use client'

import { useEffect, useMemo, useState } from 'react'
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { cn } from '@/lib/utils'
import { usePermission } from '@/lib/auth/permissions'
import { useAuthStore } from '@/lib/auth/store'
import {
  LayoutDashboard,
  Users,
  Ruler,
  ShoppingBag,
  Scissors,
  Shirt,
  ShieldCheck,
  Package,
  Truck,
  Receipt,
  BarChart2,
  Settings,
  Layers,
  AlertTriangle,
  Archive,
  ClipboardCheck,
  History,
  Shield,
  Store,
} from 'lucide-react'

type NavGroup = 'Workspace' | 'Operations' | 'Business' | 'System'

interface NavItem {
  label: string
  href: string
  icon: React.ElementType
  group: NavGroup
  roles?: string[]
  permissions?: string[]
  count?: number
  tone?: 'amber' | 'red'
}

// SB-01..SB-05: every item is gated by the backend permission that backs its
// page (RolePermissionSeeder::MATRIX), so sidebar visibility matches what the
// role can actually do. Owner's '*' wildcard satisfies all of them. Settings is
// the only ungated item — every authenticated user has a profile.
const NAV_ITEMS: NavItem[] = [
  // Workspace
  { label: 'Dashboard',    href: '/dashboard',      icon: LayoutDashboard, group: 'Workspace', permissions: ['dashboard.view'] },
  { label: 'Front Desk',   href: '/front-desk',     icon: Store,           group: 'Workspace', permissions: ['orders.create'] },
  { label: 'Orders',       href: '/orders',         icon: ShoppingBag,     group: 'Workspace', permissions: ['orders.view'] },
  { label: 'Approvals',    href: '/approvals',      icon: ClipboardCheck,  group: 'Workspace', permissions: ['measurements.approve'], tone: 'amber' },

  // Operations
  { label: 'Measurements', href: '/measurements',   icon: Ruler,           group: 'Operations', permissions: ['measurements.view'] },
  { label: 'Production',   href: '/production',     icon: Layers,          group: 'Operations', permissions: ['production.view'] },
  { label: 'Cutting',      href: '/cutting',        icon: Scissors,        group: 'Operations', permissions: ['cutting.start'] },
  { label: 'Tailoring',    href: '/tailoring',      icon: Shirt,           group: 'Operations', permissions: ['tailoring.start'] },
  { label: 'Quality',      href: '/qc',             icon: ShieldCheck,     group: 'Operations', permissions: ['qc.inspect'] },
  { label: 'Inventory',    href: '/inventory',      icon: Package,         group: 'Operations', permissions: ['inventory.view'] },
  { label: 'Damage',       href: '/damage-reports', icon: AlertTriangle,   group: 'Operations', permissions: ['damage_reports.view'] },
  { label: 'Rack',         href: '/rack',           icon: Archive,         group: 'Operations', permissions: ['rack.view'] },
  { label: 'Deliveries',   href: '/deliveries',     icon: Truck,           group: 'Operations', permissions: ['deliveries.view'], tone: 'red' },

  // Business
  { label: 'Customers',    href: '/customers',      icon: Users,           group: 'Business', permissions: ['customers.view'] },
  { label: 'Finance',      href: '/finance',        icon: Receipt,         group: 'Business', permissions: ['finance.view'] },
  { label: 'Reports',      href: '/reports',        icon: BarChart2,       group: 'Business', permissions: ['reports.view'] },
  { label: 'Mgmt Reports', href: '/reports/management', icon: BarChart2,   group: 'Business', permissions: ['reports.view'] },

  // System
  { label: 'Audit',        href: '/audit',          icon: History,         group: 'System', permissions: ['audit.view'] },
  { label: 'Admin',        href: '/admin',          icon: Shield,          group: 'System', permissions: ['users.view'] },
  { label: 'Settings',     href: '/settings',       icon: Settings,        group: 'System' },
]

const GROUP_ORDER: NavGroup[] = ['Workspace', 'Operations', 'Business', 'System']

interface SideNavProps {
  collapsed?: boolean
}

export function SideNav({ collapsed = false }: SideNavProps) {
  const pathname = usePathname()

  // Optimistic active route. App Router navigation is async, so usePathname()
  // only updates after the route commits — which makes the clicked item light
  // up late. We record the clicked href immediately and treat it as the active
  // route until the real pathname catches up.
  const [pendingHref, setPendingHref] = useState<string | null>(null)

  // Reconcile: once navigation commits (pathname changes), drop the override so
  // usePathname() becomes the single source of truth again.
  useEffect(() => {
    setPendingHref(null)
  }, [pathname])

  // The href we treat as "current" for highlighting purposes.
  const activePath = pendingHref ?? pathname

  // Exact match OR nested route (e.g. /orders/123 keeps "Orders" active).
  const isActive = (href: string) =>
    activePath === href || activePath.startsWith(href + '/')

  const { can, is } = usePermission()
  const user = useAuthStore((s) => s.user)

  // Memoize — only recomputes when user permissions change, not on every route change
  const grouped = useMemo(() => {
    const visible = NAV_ITEMS.filter((item) => {
      if (item.roles && !item.roles.some((r) => is(r))) return false
      if (item.permissions && !item.permissions.some((p) => can(p))) return false
      return true
    })
    return GROUP_ORDER.map((g) => ({
      name: g,
      items: visible.filter((i) => i.group === g),
    })).filter((g) => g.items.length > 0)
  }, [can, is])

  const initials = useMemo(() =>
    user?.name
      ? user.name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
      : 'SS',
  [user?.name])

  const roleLabel = user?.roles?.[0] ?? 'Member'

  return (
    <nav
      className={cn(
        'flex flex-col h-full bg-[var(--color-sidebar-bg)] border-r border-[var(--color-border)]',
        'select-none ss-no-scrollbar overflow-y-auto px-3.5 py-4',
        collapsed ? 'w-[68px] items-center' : 'w-[248px]',
      )}
      style={{ transition: 'width 200ms ease' }}
    >
      {/* Brand */}
      <Link
        href="/dashboard"
        className={cn('flex items-center gap-3 px-2 pb-5', collapsed && 'justify-center px-0')}
      >
        <span
          className="flex-shrink-0 flex items-center justify-center text-white font-bold rounded-[9px]"
          style={{ width: 34, height: 34, background: 'var(--color-brand)', fontSize: 14, letterSpacing: '-0.02em' }}
        >
          SS
        </span>
        {!collapsed && (
          <span className="min-w-0">
            <span className="block text-[15.5px] font-bold text-[var(--color-text-primary)] leading-none tracking-tight">
              Solo Shirts
            </span>
            <span className="block text-[11px] text-[var(--color-text-muted)] mt-1">India ERP</span>
          </span>
        )}
      </Link>

      {/* Grouped nav */}
      <div className={cn('flex-1 w-full space-y-0.5', collapsed && 'mt-1')}>
        {grouped.map((group) => (
          <div key={group.name}>
            {!collapsed && (
              <p className="px-2.5 mt-4 mb-1.5 text-[10.5px] font-semibold uppercase tracking-[0.08em] text-[var(--color-text-muted)]">
                {group.name}
              </p>
            )}
            {collapsed && <div className="my-2 h-px w-7 bg-[var(--color-border)]" aria-hidden />}
            <ul className="space-y-0.5">
              {group.items.map((item) => {
                const active = isActive(item.href)
                return (
                  <li key={item.href}>
                    <Link
                      href={item.href}
                      prefetch
                      // Fires the moment a client navigation starts (and is
                      // skipped for new-tab / modifier-key clicks), so the
                      // highlight flips instantly — before the route commits.
                      onNavigate={() => setPendingHref(item.href)}
                      title={collapsed ? item.label : undefined}
                      className={cn(
                        'group flex items-center gap-3 h-10 rounded-[10px] px-3 text-[13.5px] font-medium transition-colors',
                        active
                          ? 'bg-[var(--color-sidebar-active)] text-[var(--color-brand-dark)] font-semibold'
                          : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-gray-700)]',
                        collapsed && 'justify-center px-0 w-10 mx-auto',
                      )}
                    >
                      <item.icon
                        size={18}
                        strokeWidth={1.75}
                        className={cn(
                          'shrink-0',
                          active ? 'text-[var(--color-brand)]' : 'text-[var(--color-text-muted)] group-hover:text-[var(--color-gray-700)]',
                        )}
                      />
                      {!collapsed && <span className="truncate">{item.label}</span>}
                      {!collapsed && item.count != null && item.count > 0 && (
                        <span
                          className={cn(
                            'ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-semibold',
                            item.tone === 'red'
                              ? 'bg-[var(--bg-danger)] text-[var(--color-danger)]'
                              : active
                                ? 'bg-[var(--color-brand)] text-white'
                                : 'bg-[var(--color-brand-light)] text-[var(--color-brand-dark)]',
                          )}
                        >
                          {item.count}
                        </span>
                      )}
                    </Link>
                  </li>
                )
              })}
            </ul>
          </div>
        ))}
      </div>

      {/* User footer */}
      <div
        className={cn(
          'mt-3 pt-3.5 border-t border-[var(--color-border)] flex items-center gap-3 w-full',
          collapsed && 'justify-center',
        )}
      >
        <span
          className="flex items-center justify-center rounded-full font-semibold shrink-0"
          style={{
            width: 38,
            height: 38,
            background: 'var(--color-brand-light)',
            color: 'var(--color-brand-dark)',
            fontSize: 14,
          }}
        >
          {initials}
        </span>
        {!collapsed && (
          <>
            <span className="flex-1 min-w-0">
              <span className="block text-[13px] font-semibold text-[var(--color-text-primary)] truncate">
                {user?.name ?? 'Solo Shirts'}
              </span>
              <span className="block text-[11.5px] text-[var(--color-text-muted)] truncate">
                {roleLabel}
              </span>
            </span>
            <Link
              href="/settings"
              aria-label="Settings"
              className="text-[var(--color-text-muted)] hover:text-[var(--color-gray-700)] transition-colors"
            >
              <Settings size={17} strokeWidth={1.75} />
            </Link>
          </>
        )}
      </div>
    </nav>
  )
}
