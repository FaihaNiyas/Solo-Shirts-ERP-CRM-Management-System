'use client'

import { useState } from 'react'
import { Bell } from 'lucide-react'
import {
  useProductionNotifications,
  useMarkNotificationRead,
  useMarkAllNotificationsRead,
} from '@/lib/api/hooks/useProduction'
import { cn } from '@/lib/utils'

function timeAgo(iso: string): string {
  const then = new Date(iso).getTime()
  if (Number.isNaN(then)) return ''
  const mins = Math.round((Date.now() - then) / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hrs = Math.round(mins / 60)
  if (hrs < 24) return `${hrs}h ago`
  return `${Math.round(hrs / 24)}d ago`
}

/** In-app production notification feed (Kanban Phase F). */
export function NotificationBell() {
  const [open, setOpen] = useState(false)
  const { data } = useProductionNotifications({ polling: true })
  const markRead = useMarkNotificationRead()
  const markAll = useMarkAllNotificationsRead()

  const unread = data?.unread_count ?? 0
  const items = data?.items ?? []

  return (
    <div className="relative">
      <button
        onClick={() => setOpen((o) => !o)}
        className="relative inline-flex items-center justify-center h-10 w-10 rounded-lg border border-[var(--color-border-mid)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]"
        aria-label="Notifications"
      >
        <Bell size={16} strokeWidth={1.75} />
        {unread > 0 && (
          <span className="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
            {unread > 9 ? '9+' : unread}
          </span>
        )}
      </button>

      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute right-0 mt-2 w-80 z-50 rounded-xl border border-[var(--color-border)] bg-white shadow-[var(--shadow-xl)] overflow-hidden">
            <div className="flex items-center justify-between px-3 py-2 border-b border-[var(--color-border)]">
              <p className="text-sm font-semibold text-[var(--color-text-primary)]">Notifications</p>
              {unread > 0 && (
                <button onClick={() => markAll.mutate()} className="text-xs text-[var(--color-brand)] hover:underline">
                  Mark all read
                </button>
              )}
            </div>
            <div className="max-h-80 overflow-y-auto">
              {items.length === 0 ? (
                <p className="px-3 py-8 text-center text-sm text-[var(--color-text-muted)]">No notifications</p>
              ) : (
                items.map((n) => (
                  <button
                    key={n.id}
                    onClick={() => {
                      if (!n.is_read) markRead.mutate(n.id)
                    }}
                    className={cn(
                      'w-full text-left px-3 py-2.5 border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-surface-alt)] transition-colors',
                      !n.is_read && 'bg-amber-50/40',
                    )}
                  >
                    <div className="flex items-start gap-2">
                      {!n.is_read ? (
                        <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[var(--color-brand)]" />
                      ) : (
                        <span className="w-2 shrink-0" />
                      )}
                      <div className="min-w-0">
                        <p className="text-sm font-medium text-[var(--color-text-primary)] truncate">{n.title}</p>
                        {n.body && <p className="text-xs text-[var(--color-text-muted)] line-clamp-2">{n.body}</p>}
                        <p className="text-[10px] text-[var(--color-text-muted)] mt-0.5">{timeAgo(n.created_at)}</p>
                      </div>
                    </div>
                  </button>
                ))
              )}
            </div>
          </div>
        </>
      )}
    </div>
  )
}
