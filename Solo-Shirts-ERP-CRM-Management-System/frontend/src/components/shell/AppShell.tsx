'use client'

import { useState } from 'react'
import { cn } from '@/lib/utils'
import { SideNav } from './SideNav'
import { TopBar } from './TopBar'

const SIDEBAR_KEY = 'ss_sidebar_collapsed'

function readCollapsed(): boolean {
  if (typeof window === 'undefined') return false
  return localStorage.getItem(SIDEBAR_KEY) === 'true'
}

interface AppShellProps {
  children: React.ReactNode
}

export function AppShell({ children }: AppShellProps) {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(readCollapsed)
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false)

  return (
    <div className="flex h-screen overflow-hidden bg-[var(--color-bg)]">
      {/* Skip link — first focusable element, lets keyboard/SR users jump past the nav */}
      <a
        href="#main-content"
        className="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-[var(--color-brand)] focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white focus:shadow-[var(--shadow-md)]"
      >
        Skip to main content
      </a>

      {/* Mobile overlay */}
      {mobileSidebarOpen && (
        <div
          className="fixed inset-0 z-20 bg-black/30 md:hidden"
          onClick={() => setMobileSidebarOpen(false)}
          aria-hidden
        />
      )}

      {/* Sidebar */}
      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-30 flex-shrink-0 transition-transform duration-200',
          'md:relative md:translate-x-0',
          mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
        )}
      >
        <SideNav collapsed={sidebarCollapsed} />

        {/* Collapse toggle (desktop) */}
        <button
          onClick={() => setSidebarCollapsed((v) => {
              const next = !v
              localStorage.setItem(SIDEBAR_KEY, String(next))
              return next
            })}
          className={cn(
            'hidden md:flex absolute -right-3 top-20',
            'items-center justify-center w-6 h-6 rounded-full',
            'bg-white border border-[var(--color-border)] shadow-[var(--shadow-xs)]',
            'text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] transition-colors',
          )}
          aria-label={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        >
          <svg
            width="10"
            height="10"
            viewBox="0 0 10 10"
            fill="none"
            className={cn('transition-transform', sidebarCollapsed && 'rotate-180')}
          >
            <path d="M6.5 1.5L3 5l3.5 3.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </button>
      </aside>

      {/* Main content */}
      <div className="flex flex-col flex-1 min-w-0 overflow-hidden">
        <TopBar onToggleSidebar={() => setMobileSidebarOpen((v) => !v)} />
        <main id="main-content" tabIndex={-1} className="flex-1 overflow-y-auto ss-scroll focus:outline-none">
          <div className="ss-fade-up mx-auto max-w-[1380px] px-5 md:px-9 pt-7 pb-16">
            {children}
          </div>
        </main>
      </div>
    </div>
  )
}
