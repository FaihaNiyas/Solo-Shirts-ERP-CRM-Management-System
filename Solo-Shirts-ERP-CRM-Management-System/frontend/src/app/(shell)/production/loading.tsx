// Route-level loading UI for /production — a Kanban board of stage columns.
const COLUMNS = ['Cutting', 'Tailoring', 'Kaja Button', 'QC', 'Ironing']

export default function ProductionLoading() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <div className="h-7 w-40 rounded-lg ss-shimmer" />
          <div className="h-3.5 w-56 rounded ss-shimmer" />
        </div>
        <div className="h-10 w-28 rounded-lg ss-shimmer" />
      </div>

      {/* Kanban columns */}
      <div className="flex gap-4 overflow-x-auto pb-2">
        {COLUMNS.map((_, col) => (
          <div key={col} className="flex-shrink-0 w-[280px] space-y-3">
            {/* Column header */}
            <div className="flex items-center justify-between px-1">
              <div className="h-4 w-24 rounded ss-shimmer" />
              <div className="h-5 w-7 rounded-full ss-shimmer" />
            </div>
            {/* Cards */}
            {[...Array(3 + (col % 2))].map((_, i) => (
              <div key={i} className="ss-card ss-card-pad space-y-3" style={{ opacity: 1 - i * 0.15 }}>
                <div className="flex items-center justify-between">
                  <div className="h-4 w-20 rounded ss-shimmer" />
                  <div className="h-5 w-12 rounded-full ss-shimmer" />
                </div>
                <div className="h-3 w-32 rounded ss-shimmer" />
                <div className="h-3 w-24 rounded ss-shimmer" />
                <div className="flex items-center gap-2 pt-1">
                  <div className="h-6 w-6 rounded-full ss-shimmer" />
                  <div className="h-3 w-16 rounded ss-shimmer" />
                </div>
              </div>
            ))}
          </div>
        ))}
      </div>
    </div>
  )
}
