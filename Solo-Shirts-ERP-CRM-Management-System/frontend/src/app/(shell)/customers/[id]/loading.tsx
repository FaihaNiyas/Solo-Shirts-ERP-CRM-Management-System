// Route-level loading UI for /customers/[id] — profile header + detail panels.
export default function CustomerDetailLoading() {
  return (
    <div className="space-y-6">
      {/* Profile header */}
      <div className="flex items-center gap-4">
        <div className="h-14 w-14 rounded-full ss-shimmer" />
        <div className="space-y-2">
          <div className="h-6 w-48 rounded-lg ss-shimmer" />
          <div className="h-3.5 w-32 rounded ss-shimmer" />
        </div>
      </div>

      {/* Summary metrics */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {[...Array(4)].map((_, i) => (
          <div key={i} className="h-20 rounded-xl ss-shimmer" style={{ opacity: 1 - i * 0.12 }} />
        ))}
      </div>

      {/* Body panels */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div className="h-64 rounded-xl ss-shimmer" />
        <div className="h-64 rounded-xl ss-shimmer" />
      </div>
    </div>
  )
}
