// Route-level loading UI for /dashboard. Streamed in instantly while the
// dashboard's server data resolves — the sidebar (in the persistent layout)
// stays mounted and already shows "Dashboard" as active.
export default function DashboardLoading() {
  return (
    <div className="space-y-7">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <div className="h-7 w-48 rounded-lg ss-shimmer" />
          <div className="h-3.5 w-64 rounded ss-shimmer" />
        </div>
        <div className="h-10 w-32 rounded-lg ss-shimmer" />
      </div>

      {/* KPI cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {[...Array(4)].map((_, i) => (
          <div key={i} className="ss-kpi space-y-3">
            <div className="h-3 w-20 rounded ss-shimmer" />
            <div className="h-9 w-28 rounded ss-shimmer" />
            <div className="h-2.5 w-16 rounded ss-shimmer" />
          </div>
        ))}
      </div>

      {/* Two wide panels (chart + recent activity) */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="ss-card ss-card-pad lg:col-span-2 space-y-4">
          <div className="h-4 w-40 rounded ss-shimmer" />
          <div className="h-56 w-full rounded-xl ss-shimmer" />
        </div>
        <div className="ss-card ss-card-pad space-y-3">
          <div className="h-4 w-32 rounded ss-shimmer" />
          {[...Array(5)].map((_, i) => (
            <div key={i} className="h-11 rounded-xl ss-shimmer" style={{ opacity: 1 - i * 0.14 }} />
          ))}
        </div>
      </div>
    </div>
  )
}
