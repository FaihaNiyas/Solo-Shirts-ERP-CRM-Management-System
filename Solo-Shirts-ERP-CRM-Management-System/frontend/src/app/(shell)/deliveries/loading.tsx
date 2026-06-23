// Route-level loading UI for /deliveries — data table.
export default function DeliveriesLoading() {
  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <div className="h-7 w-36 rounded-lg ss-shimmer" />
        <div className="h-3.5 w-56 rounded ss-shimmer" />
      </div>

      <div className="ss-card overflow-hidden">
        <div className="flex items-center gap-4 px-5 py-3 border-b border-[var(--color-border)]">
          {[24, 18, 22, 14, 16, 18].map((w, i) => (
            <div key={i} className="h-3 rounded ss-shimmer" style={{ width: `${w * 4}px` }} />
          ))}
        </div>
        {[...Array(8)].map((_, i) => (
          <div
            key={i}
            className="flex items-center gap-4 px-5 py-4 border-b border-[var(--color-border)] last:border-0"
            style={{ opacity: 1 - i * 0.08 }}
          >
            <div className="h-4 w-24 rounded ss-shimmer" />
            <div className="h-4 w-20 rounded ss-shimmer" />
            <div className="h-4 w-28 rounded ss-shimmer" />
            <div className="h-6 w-16 rounded-full ss-shimmer" />
            <div className="h-4 w-20 rounded ss-shimmer" />
            <div className="ml-auto h-7 w-16 rounded-lg ss-shimmer" />
          </div>
        ))}
      </div>
    </div>
  )
}
