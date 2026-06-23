// Route-level loading UI for /orders — a filterable data grid.
export default function OrdersLoading() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <div className="h-7 w-32 rounded-lg ss-shimmer" />
          <div className="h-3.5 w-52 rounded ss-shimmer" />
        </div>
        <div className="h-10 w-36 rounded-lg ss-shimmer" />
      </div>

      {/* Filter bar */}
      <div className="flex flex-wrap items-center gap-3">
        <div className="h-10 w-64 rounded-lg ss-shimmer" />
        <div className="h-10 w-32 rounded-lg ss-shimmer" />
        <div className="h-10 w-32 rounded-lg ss-shimmer" />
        <div className="ml-auto h-10 w-24 rounded-lg ss-shimmer" />
      </div>

      {/* Table */}
      <div className="ss-card overflow-hidden">
        {/* Table header row */}
        <div className="flex items-center gap-4 px-5 py-3 border-b border-[var(--color-border)]">
          {[28, 20, 24, 16, 20, 14].map((w, i) => (
            <div key={i} className="h-3 rounded ss-shimmer" style={{ width: `${w * 4}px` }} />
          ))}
        </div>
        {/* Rows */}
        {[...Array(8)].map((_, i) => (
          <div
            key={i}
            className="flex items-center gap-4 px-5 py-4 border-b border-[var(--color-border)] last:border-0"
            style={{ opacity: 1 - i * 0.08 }}
          >
            <div className="h-4 w-28 rounded ss-shimmer" />
            <div className="h-4 w-20 rounded ss-shimmer" />
            <div className="h-4 w-24 rounded ss-shimmer" />
            <div className="h-6 w-16 rounded-full ss-shimmer" />
            <div className="h-4 w-20 rounded ss-shimmer" />
            <div className="ml-auto h-7 w-7 rounded-lg ss-shimmer" />
          </div>
        ))}
      </div>
    </div>
  )
}
