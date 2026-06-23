import React from 'react'
import Link from 'next/link'
import { Paintbrush, SlidersHorizontal, Eye, User } from 'lucide-react'

const NAV = [
  { href: '/settings/profile',       label: 'Profile',       icon: User },
  { href: '/settings/appearance',    label: 'Appearance',    icon: Paintbrush },
  { href: '/settings/preferences',   label: 'Preferences',   icon: SlidersHorizontal },
  { href: '/settings/accessibility', label: 'Accessibility', icon: Eye },
]

export default function SettingsLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex gap-6">
      <nav className="hidden md:flex flex-col w-48 shrink-0 gap-1 pt-1">
        <p className="px-3 mb-2 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">
          Settings
        </p>
        {NAV.map(({ href, label, icon: Icon }) => (
          <Link
            key={href}
            href={href}
            className="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-text-primary)] transition-colors"
          >
            <Icon size={15} strokeWidth={1.75} />
            {label}
          </Link>
        ))}
      </nav>
      <div className="flex-1 min-w-0">{children}</div>
    </div>
  )
}
