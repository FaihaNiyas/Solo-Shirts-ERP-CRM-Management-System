export default function ShellLoading() {
  return (
    <div className="space-y-7">
      {/* Page header skeleton */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <div className="h-7 w-44 rounded-lg ss-shimmer" />
          <div className="h-3.5 w-56 rounded ss-shimmer" />
        </div>
        <div className="h-10 w-28 rounded-lg ss-shimmer" />
      </div>

      {/* Metric cards skeleton */}
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {[...Array(4)].map((_, i) => (
          <div key={i} className="ss-kpi space-y-3">
            <div className="h-3 w-20 rounded ss-shimmer" />
            <div className="h-9 w-28 rounded ss-shimmer" />
            <div className="h-2.5 w-16 rounded ss-shimmer" />
          </div>
        ))}
      </div>

      {/* Content skeleton */}
      <div className="ss-card ss-card-pad space-y-3">
        {[...Array(6)].map((_, i) => (
          <div key={i} className="h-12 rounded-xl ss-shimmer" style={{ opacity: 1 - i * 0.12 }} />
        ))}
      </div>
    </div>
  )
}
