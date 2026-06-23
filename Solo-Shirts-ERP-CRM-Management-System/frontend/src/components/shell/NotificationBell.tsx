'use client'

import { useState } from 'react'
import { Bell } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { cn } from '@/lib/utils'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'
import { usePermission } from '@/lib/auth/permissions'

interface Notification {
  id: number
  title: string
  body: string
  read_at: string | null
  created_at: string
  type: string
}

export function NotificationBell() {
  const [open, setOpen] = useState(false)
  const { can } = usePermission()
  // Only roles that hold notifications.view may read the feed — otherwise the
  // poll would 403 every interval. Hide the bell entirely for everyone else.
  const canView = can('notifications.view')

  const { data } = useQuery({
    queryKey: queryKeys.notifications(),
    queryFn: () => apiGet<{ data: Notification[]; unread_count: number }>(ENDPOINTS.notifications),
    refetchInterval: 60_000,
    enabled: canView,
  })

  if (!canView) return null

  const unread = data?.data?.unread_count ?? 0
  const list = data?.data?.data ?? []

  return (
    <div className="relative">
      <button
        onClick={() => setOpen((v) => !v)}
        className="relative flex items-center justify-center w-8 h-8 rounded-lg hover:bg-[var(--color-surface-alt)] text-[var(--color-text-secondary)] transition-colors"
        aria-label={`Notifications${unread ? ` (${unread} unread)` : ''}`}
      >
        <Bell size={18} strokeWidth={1.75} />
        {unread > 0 && (
          <span
            className="absolute top-1 right-1 flex items-center justify-center min-w-[16px] h-4 rounded-full bg-[var(--color-brand)] text-white text-[10px] font-semibold px-0.5"
            aria-hidden
          >
            {unread > 99 ? '99+' : unread}
          </span>
        )}
      </button>

      {open && (
        <>
          <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} aria-hidden />
          <div
            className={cn(
              'absolute right-0 top-full mt-2 z-20 w-80',
              'bg-white rounded-xl border border-[var(--color-border)] shadow-[var(--shadow-lg)]',
              'overflow-hidden',
            )}
          >
            <div className="flex items-center justify-between px-4 py-3 border-b border-[var(--color-border)]">
              <span className="text-sm font-semibold text-[var(--color-text-primary)]">
                Notifications
              </span>
              {unread > 0 && (
                <span className="text-xs text-[var(--color-brand)] font-medium">
                  {unread} unread
                </span>
              )}
            </div>

            <ul className="max-h-80 overflow-y-auto divide-y divide-[var(--color-border)]">
              {list.length === 0 && (
                <li className="py-10 text-center text-sm text-[var(--color-text-muted)]">
                  No notifications
                </li>
              )}
              {list.slice(0, 15).map((n) => (
                <li
                  key={n.id}
                  className={cn(
                    'px-4 py-3 text-sm',
                    !n.read_at && 'bg-[var(--color-brand-50)]',
                  )}
                >
                  <p className="font-medium text-[var(--color-text-primary)] leading-snug">{n.title}</p>
                  <p className="mt-0.5 text-[var(--color-text-secondary)] text-xs leading-snug line-clamp-2">
                    {n.body}
                  </p>
                </li>
              ))}
            </ul>
          </div>
        </>
      )}
    </div>
  )
}
