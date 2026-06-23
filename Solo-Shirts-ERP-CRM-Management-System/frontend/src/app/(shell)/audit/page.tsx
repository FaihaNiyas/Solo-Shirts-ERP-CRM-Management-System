'use client'

import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { PageHeader } from '@/components/ui/page-header'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { useAuth } from '@/lib/auth/useAuth'
import { Shield } from 'lucide-react'
import { format } from 'date-fns'

interface Activity {
  id: number
  log_name?: string
  event?: string
  description?: string
  subject_type?: string
  subject_id?: number
  causer_id?: number
  causer_name?: string | null
  created_at?: string
}

// "App\\Modules\\Customer\\Models\\Customer" → "Customer"
function shortSubject(type?: string): string {
  if (!type) return '—'
  const parts = type.split('\\')
  return parts[parts.length - 1] || type
}

export default function AuditPage() {
  const { user } = useAuth()
  const [filter, setFilter] = useState('')

  const isAllowed = user?.roles?.includes('Owner') || user?.roles?.includes('Admin')

  const { data: logs = [], isLoading } = useQuery<Activity[]>({
    queryKey: ['audit'],
    queryFn: () => apiGet<Activity[]>(ENDPOINTS.auditActivities).then((r) => r.data),
    enabled: isAllowed,
  })

  const filtered = useMemo(() => {
    if (!filter.trim()) return logs
    const q = filter.toLowerCase()
    return logs.filter(
      (l) =>
        l.event?.toLowerCase().includes(q) ||
        l.description?.toLowerCase().includes(q) ||
        shortSubject(l.subject_type).toLowerCase().includes(q) ||
        l.causer_name?.toLowerCase().includes(q),
    )
  }, [logs, filter])

  if (!isAllowed) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center">
        <Shield size={40} strokeWidth={1.25} className="text-[var(--color-text-muted)]" />
        <p className="text-sm text-[var(--color-text-muted)]">Owner or Admin access required</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Audit Log" subtitle="Read-only. Recent recorded actions (latest 200)." />

      <div>
        <input
          type="text"
          value={filter}
          onChange={(e) => setFilter(e.target.value)}
          placeholder="Filter by event, subject or user…"
          className="h-9 w-72 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
        />
      </div>

      {isLoading && <TableSkeleton rows={10} cols={4} />}

      {!isLoading && filtered.length === 0 && (
        <p className="text-sm text-[var(--color-text-muted)] py-8 text-center">No audit logs found</p>
      )}

      {!isLoading && filtered.length > 0 && (
        <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-[var(--color-surface-alt)]">
              <tr>
                {['When', 'User', 'Event', 'Subject', 'Description'].map((h) => (
                  <th key={h} className="px-4 py-2 text-left text-xs font-semibold text-[var(--color-text-muted)]">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-[var(--color-border)]">
              {filtered.map((log) => (
                <tr key={log.id} className="hover:bg-[var(--color-surface-alt)] transition-colors">
                  <td className="px-4 py-3 whitespace-nowrap text-xs text-[var(--color-text-muted)]">
                    {log.created_at ? format(new Date(log.created_at), 'dd MMM yy HH:mm') : '—'}
                  </td>
                  <td className="px-4 py-3 font-medium">
                    {log.causer_name ?? (log.causer_id ? `#${log.causer_id}` : 'System')}
                  </td>
                  <td className="px-4 py-3">
                    <span className="font-mono text-xs px-1.5 py-0.5 bg-[var(--color-surface-alt)] rounded">
                      {log.event ?? '—'}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-xs text-[var(--color-text-muted)]">
                    <span>{shortSubject(log.subject_type)}</span>
                    {log.subject_id && <span className="font-mono ml-1">#{log.subject_id}</span>}
                  </td>
                  <td className="px-4 py-3 text-xs text-[var(--color-text-muted)]">
                    {log.description ?? '—'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
