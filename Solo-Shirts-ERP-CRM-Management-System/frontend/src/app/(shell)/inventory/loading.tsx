// Route-level loading UI for /inventory — stock summary KPIs + fabric-roll grid.
export default function InventoryLoading() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <div className="h-7 w-36 rounded-lg ss-shimmer" />
          <div className="h-3.5 w-56 rounded ss-shimmer" />
        </div>
        <div className="h-10 w-32 rounded-lg ss-shimmer" />
      </div>

      {/* Stock KPIs — remaining / reserved / available are distinct values */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {[...Array(3)].map((_, i) => (
          <div key={i} className="ss-kpi space-y-3">
            <div className="h-3 w-24 rounded ss-shimmer" />
            <div className="h-8 w-24 rounded ss-shimmer" />
          </div>
        ))}
      </div>

      {/* Filter row */}
      <div className="flex flex-wrap items-center gap-3">
        <div className="h-10 w-56 rounded-lg ss-shimmer" />
        <div className="h-10 w-28 rounded-lg ss-shimmer" />
        <div className="h-10 w-28 rounded-lg ss-shimmer" />
      </div>

      {/* Table */}
      <div className="ss-card overflow-hidden">
        {[...Array(7)].map((_, i) => (
          <div
            key={i}
            className="flex items-center gap-4 px-5 py-4 border-b border-[var(--color-border)] last:border-0"
            style={{ opacity: 1 - i * 0.1 }}
          >
            <div className="h-9 w-9 rounded-lg ss-shimmer" />
            <div className="h-4 w-32 rounded ss-shimmer" />
            <div className="h-4 w-20 rounded ss-shimmer" />
            <div className="h-4 w-16 rounded ss-shimmer" />
            <div className="h-6 w-20 rounded-full ss-shimmer" />
            <div className="ml-auto h-4 w-24 rounded ss-shimmer" />
          </div>
        ))}
      </div>
    </div>
  )
}
