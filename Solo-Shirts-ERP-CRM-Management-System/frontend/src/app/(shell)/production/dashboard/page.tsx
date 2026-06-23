'use client'

import Link from 'next/link'
import { Shield, AlertTriangle, ArrowLeft } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { usePermission } from '@/lib/auth/permissions'
import { useProductionDashboard } from '@/lib/api/hooks/useProduction'
import { productionStateLabel } from '@/lib/orders/productionState'
import { cn } from '@/lib/utils'

const STAGE_ORDER = [
  'fabric_allocated', 'cutting', 'tailoring', 'kaja_button',
  'finishing', 'qc', 'rework', 'packing', 'ready_for_delivery',
]

type Tone = 'default' | 'alert' | 'warn' | 'success'

const TONE: Record<Tone, string> = {
  default: 'text-[var(--color-text-primary)]',
  alert: 'text-red-600',
  warn: 'text-[var(--color-brand)]',
  success: 'text-green-600',
}

function Metric({ label, value, tone = 'default' }: { label: string; value: number; tone?: Tone }) {
  return (
    <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
      <p className="text-xs text-[var(--color-text-muted)] mb-1">{label}</p>
      <p className={cn('text-2xl font-semibold', TONE[value > 0 ? tone : 'default'])}>{value}</p>
    </div>
  )
}

export default function ProductionDashboardPage() {
  const { can } = usePermission()
  const allowed = can('production.dashboard.view')
  const { data, isLoading } = useProductionDashboard({ polling: true })

  if (!allowed) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center">
        <Shield size={40} strokeWidth={1.25} className="text-[var(--color-text-muted)]" />
        <p className="text-sm text-[var(--color-text-muted)]">
          You don&apos;t have permission to view the production dashboard.
        </p>
      </div>
    )
  }

  const maxStage = data ? Math.max(1, ...STAGE_ORDER.map((s) => data.by_stage[s] ?? 0)) : 1
  const maxHours = data ? Math.max(1, ...Object.values(data.avg_hours_in_stage)) : 1

  return (
    <div className="space-y-6">
      <PageHeader
        title="Production Dashboard"
        subtitle="Live operational status"
        actions={
          <Link
            href="/production"
            className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-10 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]"
          >
            <ArrowLeft size={15} strokeWidth={1.75} /> Board
          </Link>
        }
      />

      {isLoading || !data ? (
        <TableSkeleton rows={6} cols={4} />
      ) : (
        <>
          {/* Bottleneck banner */}
          {data.bottleneck_stage && (
            <div className="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
              <AlertTriangle size={16} />
              <span>
                Bottleneck: <strong>{productionStateLabel(data.bottleneck_stage.stage)}</strong> — items spend
                an average of <strong>{data.bottleneck_stage.avg_hours}h</strong> here.
              </span>
            </div>
          )}

          {/* Key metrics */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <Metric label="Active in production" value={data.total_active} />
            <Metric label="Delayed" value={data.delayed} tone="alert" />
            <Metric label="Urgent / High" value={data.urgent} tone="warn" />
            <Metric label="On hold" value={data.on_hold} tone="warn" />
            <Metric label="In rework" value={data.in_rework} tone="warn" />
            <Metric label="Pending QC" value={data.pending_qc} />
            <Metric label="Ready for delivery" value={data.ready_for_delivery} tone="success" />
            <Metric label="Completed today" value={data.completed_today} tone="success" />
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {/* Items per stage */}
            <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
              <p className="text-sm font-semibold text-[var(--color-text-primary)] mb-3">Items per stage</p>
              <div className="space-y-2">
                {STAGE_ORDER.map((s) => {
                  const count = data.by_stage[s] ?? 0
                  return (
                    <div key={s} className="flex items-center gap-3">
                      <span className="w-28 shrink-0 text-xs text-[var(--color-text-secondary)]">
                        {productionStateLabel(s)}
                      </span>
                      <div className="flex-1 h-2 rounded-full bg-[var(--color-surface-alt)] overflow-hidden">
                        <div
                          className="h-full rounded-full bg-[var(--color-brand)]"
                          style={{ width: `${(count / maxStage) * 100}%` }}
                        />
                      </div>
                      <span className="w-6 text-right text-xs font-semibold text-[var(--color-text-primary)]">
                        {count}
                      </span>
                    </div>
                  )
                })}
              </div>
            </div>

            {/* Average time in stage */}
            <div className="rounded-xl border border-[var(--color-border)] bg-white p-4">
              <p className="text-sm font-semibold text-[var(--color-text-primary)] mb-3">
                Average time in stage
              </p>
              {Object.keys(data.avg_hours_in_stage).length === 0 ? (
                <p className="text-sm text-[var(--color-text-muted)] py-4">Not enough transition history yet.</p>
              ) : (
                <div className="space-y-2">
                  {STAGE_ORDER.filter((s) => data.avg_hours_in_stage[s] !== undefined).map((s) => {
                    const hours = data.avg_hours_in_stage[s]
                    const isBottleneck = data.bottleneck_stage?.stage === s
                    return (
                      <div key={s} className="flex items-center gap-3">
                        <span className="w-28 shrink-0 text-xs text-[var(--color-text-secondary)]">
                          {productionStateLabel(s)}
                        </span>
                        <div className="flex-1 h-2 rounded-full bg-[var(--color-surface-alt)] overflow-hidden">
                          <div
                            className={cn('h-full rounded-full', isBottleneck ? 'bg-red-400' : 'bg-[var(--color-brand-light)]')}
                            style={{ width: `${(hours / maxHours) * 100}%` }}
                          />
                        </div>
                        <span className={cn('w-12 text-right text-xs font-semibold', isBottleneck ? 'text-red-600' : 'text-[var(--color-text-primary)]')}>
                          {hours}h
                        </span>
                      </div>
                    )
                  })}
                </div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  )
}
