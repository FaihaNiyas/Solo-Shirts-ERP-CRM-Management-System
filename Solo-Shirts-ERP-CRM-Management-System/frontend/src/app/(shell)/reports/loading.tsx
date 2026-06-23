// Route-level loading UI for /reports — report cards grid.
export default function ReportsLoading() {
  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <div className="h-7 w-36 rounded-lg ss-shimmer" />
        <div className="h-3.5 w-60 rounded ss-shimmer" />
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {[...Array(6)].map((_, i) => (
          <div key={i} className="h-32 rounded-xl ss-shimmer" style={{ opacity: 1 - i * 0.1 }} />
        ))}
      </div>
    </div>
  )
}
