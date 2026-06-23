'use client'

import Link from 'next/link'
import { useQuery } from '@tanstack/react-query'
import { ShoppingBag, Users, Truck, Receipt, TrendingUp, AlertTriangle, Clock, CheckCircle, ArrowRight } from 'lucide-react'
import { cn, formatINR } from '@/lib/utils'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'
import { useOrders } from '@/lib/api/hooks/useOrders'
import { useProductionBoard } from '@/lib/api/hooks/useProduction'
import { StatusBadge } from '@/components/ui/status-badge'
import { MetricCard } from '@/components/ui/metric-card'
import { PageHeader } from '@/components/ui/page-header'

interface FinanceSummary {
  total_revenue: number
  outstanding_amount: number
  orders_today: number
  deliveries_pending: number
  production_in_progress: number
  low_stock_alerts: number
  qc_pending: number
  completed_today: number
}

const PRODUCTION_ORDER = [
  'Draft', 'FabricAllocated', 'Cutting', 'Tailoring',
  'KajaButton', 'Finishing', 'QC', 'Packing', 'ReadyForDelivery', 'Rework',
]

const STAGE_DISPLAY: Record<string, string> = {
  Draft: 'Draft',
  FabricAllocated: 'Fabric Ready',
  Cutting: 'Cutting',
  Tailoring: 'Tailoring',
  KajaButton: 'Kaja Button',
  Finishing: 'Finishing',
  QC: 'QC',
  Packing: 'Packing',
  ReadyForDelivery: 'Ready',
  Rework: 'Rework',
}

export default function DashboardPage() {
  const { data: financeData, isLoading } = useQuery({
    queryKey: queryKeys.financeDashboard(),
    queryFn: () => apiGet<FinanceSummary>(ENDPOINTS.financeDashboard),
  })

  const { data: recentOrdersData, isLoading: ordersLoading } = useOrders({ per_page: 5 })
  const { data: board } = useProductionBoard({ polling: false })

  const summary = financeData?.data
  const recentOrders = recentOrdersData?.data ?? []

  const productionCounts = PRODUCTION_ORDER
    .map((state) => ({ state, count: board?.columns?.[state]?.length ?? 0 }))
    .filter(({ count }) => count > 0)
  const maxCount = Math.max(...productionCounts.map((p) => p.count), 1)
  const pipelineSeries = PRODUCTION_ORDER.map((s) => board?.columns?.[s]?.length ?? 0)

  const fmt = (n: number | undefined) => (isLoading ? '—' : (n ?? 0))

  return (
    <div className="space-y-7">
      <PageHeader title="Dashboard" description="Overview of today's operations" />

      {/* Metric grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <MetricCard
          label="Today's Orders"
          value={fmt(summary?.orders_today)}
          icon={ShoppingBag}
          trend="New orders today"
          trendDir="up"
        />
        <MetricCard
          label="Revenue"
          value={isLoading ? '—' : formatINR(summary?.total_revenue ?? 0)}
          icon={Receipt}
          variant="positive"
          trend="Total collected"
          trendDir="up"
        />
        <MetricCard
          label="Outstanding"
          value={isLoading ? '—' : formatINR(summary?.outstanding_amount ?? 0)}
          icon={TrendingUp}
          variant={summary?.outstanding_amount ? 'warning' : 'default'}
          trend="Pending collection"
          trendDir="flat"
        />
        <MetricCard
          label="Deliveries Due"
          value={fmt(summary?.deliveries_pending)}
          icon={Truck}
          variant={(summary?.deliveries_pending ?? 0) > 5 ? 'warning' : 'default'}
          trend="Awaiting dispatch"
          trendDir="flat"
        />
        <MetricCard
          label="In Production"
          value={fmt(summary?.production_in_progress)}
          icon={Clock}
          trend="Active items in pipeline"
          trendDir="up"
          data={pipelineSeries.some((n) => n > 0) ? pipelineSeries : undefined}
        />
        <MetricCard
          label="QC Pending"
          value={fmt(summary?.qc_pending)}
          icon={CheckCircle}
          variant={(summary?.qc_pending ?? 0) > 10 ? 'warning' : 'default'}
          trend="Awaiting inspection"
          trendDir="flat"
        />
        <MetricCard
          label="Low Stock"
          value={fmt(summary?.low_stock_alerts)}
          icon={AlertTriangle}
          variant={(summary?.low_stock_alerts ?? 0) > 0 ? 'danger' : 'positive'}
          trend="Fabric rolls to reorder"
          trendDir={(summary?.low_stock_alerts ?? 0) > 0 ? 'down' : 'up'}
        />
        <MetricCard
          label="Completed Today"
          value={fmt(summary?.completed_today)}
          icon={Users}
          variant="positive"
          trend="Orders fulfilled"
          trendDir="up"
        />
      </div>

      {/* Live sections */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {/* Recent Orders */}
        <section className="ss-card ss-card-pad">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-base font-semibold text-[var(--color-text-primary)]">Recent Orders</h2>
            <Link
              href="/orders"
              className="inline-flex items-center gap-1 text-xs font-medium text-[var(--color-brand-dark)] hover:underline"
            >
              View all <ArrowRight size={13} strokeWidth={2} />
            </Link>
          </div>

          {ordersLoading ? (
            <div className="space-y-2">
              {[...Array(5)].map((_, i) => (
                <div key={i} className="h-12 rounded-xl ss-shimmer" style={{ opacity: 1 - i * 0.13 }} />
              ))}
            </div>
          ) : recentOrders.length === 0 ? (
            <p className="py-10 text-center text-sm text-[var(--color-text-muted)]">No orders yet</p>
          ) : (
            <div className="-mx-2">
              {recentOrders.map((order) => (
                <Link
                  key={order.id}
                  href={`/orders/${order.id}`}
                  className="flex items-center gap-3 px-2.5 py-2.5 rounded-xl hover:bg-[var(--color-surface-alt)] transition-colors"
                >
                  <div className="flex-1 min-w-0">
                    <p className="ss-mono text-[13px] font-medium text-[var(--color-text-primary)] truncate">
                      {order.order_code}
                    </p>
                    <p className="text-xs text-[var(--color-text-muted)] truncate mt-0.5">
                      {order.customer_name ?? '—'}
                    </p>
                  </div>
                  <StatusBadge status={order.status ?? 'pending'} showDot />
                  <span className="text-sm font-semibold text-[var(--color-text-primary)] tabular-nums shrink-0 w-20 text-right">
                    {formatINR(order.total_amount ?? 0)}
                  </span>
                </Link>
              ))}
            </div>
          )}
        </section>

        {/* Production Status */}
        <section className="ss-card ss-card-pad">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-base font-semibold text-[var(--color-text-primary)]">Production Status</h2>
            <Link
              href="/production"
              className="inline-flex items-center gap-1 text-xs font-medium text-[var(--color-brand-dark)] hover:underline"
            >
              View board <ArrowRight size={13} strokeWidth={2} />
            </Link>
          </div>

          {productionCounts.length === 0 ? (
            <p className="py-10 text-center text-sm text-[var(--color-text-muted)]">
              No items in production
            </p>
          ) : (
            <div className="space-y-3.5">
              {productionCounts.map(({ state, count }) => (
                <div key={state} className="flex items-center gap-3">
                  <span className="w-24 text-xs font-medium text-[var(--color-text-secondary)] truncate shrink-0">
                    {STAGE_DISPLAY[state] ?? state}
                  </span>
                  <div className="flex-1 h-2 rounded-full bg-[var(--bg-neutral)] overflow-hidden">
                    <div
                      className={cn('h-full rounded-full', state === 'Rework' ? 'bg-[var(--color-warning)]' : 'bg-[var(--color-brand)]')}
                      style={{ width: `${(count / maxCount) * 100}%`, transition: 'width 400ms ease' }}
                    />
                  </div>
                  <span className="text-xs font-semibold text-[var(--color-text-primary)] w-6 text-right tabular-nums shrink-0">
                    {count}
                  </span>
                </div>
              ))}
            </div>
          )}
        </section>
      </div>
    </div>
  )
}
