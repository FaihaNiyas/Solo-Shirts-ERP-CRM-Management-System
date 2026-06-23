'use client'

import { Menu } from 'lucide-react'
import { cn } from '@/lib/utils'
import { BranchSwitcher } from './BranchSwitcher'
import { NotificationBell } from './NotificationBell'
import { UserMenu } from './UserMenu'
import { GlobalSearch, GlobalSearchTrigger } from './GlobalSearch'

interface TopBarProps {
  onToggleSidebar?: () => void
  className?: string
}

export function TopBar({ onToggleSidebar, className }: TopBarProps) {
  return (
    <header
      className={cn(
        'h-16 flex items-center gap-4 px-4 md:px-7 shrink-0',
        'border-b border-[var(--color-border)]',
        'bg-[rgba(255,252,245,0.86)] backdrop-blur-md',
        'sticky top-0 z-30',
        className,
      )}
    >
      {/* Mobile menu toggle */}
      <button
        onClick={onToggleSidebar}
        aria-label="Toggle navigation"
        className="md:hidden flex items-center justify-center w-10 h-10 -ml-1 rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]"
      >
        <Menu size={20} strokeWidth={1.75} />
      </button>

      {/* Left: Branch switcher */}
      <div className="flex items-center gap-3 shrink-0">
        <BranchSwitcher />
      </div>

      {/* Center: Global search */}
      <div className="flex-1 flex justify-center min-w-0">
        <GlobalSearchTrigger />
      </div>

      {/* Right: Notifications + user */}
      <div className="flex items-center gap-2.5 shrink-0">
        <NotificationBell />
        <UserMenu />
      </div>

      <GlobalSearch />
    </header>
  )
}
