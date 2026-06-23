'use client'

import Link from 'next/link'
import dynamic from 'next/dynamic'
import { ListChecks, Users, LayoutDashboard } from 'lucide-react'
import { useProductionBoard } from '@/lib/api/hooks/useProduction'
import { usePermission } from '@/lib/auth/permissions'
import { PageHeader } from '@/components/ui/page-header'
import { NotificationBell } from '@/components/production/NotificationBell'
import type { ProductionItem } from '@/lib/api/schemas/production'

// Lazy-load the heavy production workspace so the page shell + stat cards paint
// first; the views (and their framer-motion cards) stream in after. ssr:false — it
// is a live, client-only view that already sits behind the auth gate.
const ProductionWorkspace = dynamic(
  () => import('@/components/production/ProductionWorkspace').then((m) => m.ProductionWorkspace),
  { ssr: false, loading: () => <div className="h-96 rounded-xl ss-shimmer" /> },
)

function countOverdue(items: ProductionItem[]) {
  return items.filter((i) => i.is_overdue).length
}

function countRework(items: ProductionItem[]) {
  return items.filter((i) => (i.rework_count ?? 0) > 0).length
}

export default function ProductionPage() {
  const { can } = usePermission()
  const { data: board } = useProductionBoard({ polling: true })
  const allItems: ProductionItem[] = Object.values(board?.columns ?? {}).flat()

  const totalActive = allItems.length
  const overdueCount = countOverdue(allItems)
  const reworkCount = countRework(allItems)
  const readyCount = (board?.columns?.['ReadyForDelivery'] ?? []).length

  return (
    <div className="space-y-6">
      <PageHeader
        title="Production Board"
        subtitle="Live production status"
        actions={
          <div className="flex items-center gap-2">
            <NotificationBell />
            {can('production.dashboard.view') && (
              <Link href="/production/dashboard" className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-10 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
                <LayoutDashboard size={15} strokeWidth={1.75} /> Dashboard
              </Link>
            )}
            {can('production.supervisor.assign') && (
              <Link href="/production/supervisors" className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-10 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
                <Users size={15} strokeWidth={1.75} /> Supervisors
              </Link>
            )}
            <Link href="/production/queue" className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-10 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
              <ListChecks size={15} strokeWidth={1.75} /> Queue
            </Link>
          </div>
        }
      />

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[
          { label: 'Active Items', value: totalActive },
          { label: 'Overdue', value: overdueCount, alert: overdueCount > 0 },
          { label: 'In Rework', value: reworkCount, warn: reworkCount > 0 },
          { label: 'Ready for Delivery', value: readyCount, success: readyCount > 0 },
        ].map(({ label, value, alert, warn, success }) => (
          <div key={label} className="rounded-xl border border-[var(--color-border)] bg-white p-4">
            <p className="text-xs text-[var(--color-text-muted)] mb-1">{label}</p>
            <p className={`text-2xl font-semibold ${alert ? 'text-red-600' : warn ? 'text-[var(--color-brand)]' : success ? 'text-green-600' : 'text-[var(--color-text-primary)]'}`}>
              {value}
            </p>
          </div>
        ))}
      </div>

      <ProductionWorkspace />
    </div>
  )
}
