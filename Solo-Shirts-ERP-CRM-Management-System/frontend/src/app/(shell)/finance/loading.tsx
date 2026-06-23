// Route-level loading UI for /finance — summary cards + section links.
export default function FinanceLoading() {
  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <div className="h-7 w-40 rounded-lg ss-shimmer" />
        <div className="h-3.5 w-64 rounded ss-shimmer" />
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {[...Array(4)].map((_, i) => (
          <div key={i} className="h-24 rounded-xl ss-shimmer" style={{ opacity: 1 - i * 0.12 }} />
        ))}
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {[...Array(6)].map((_, i) => (
          <div key={i} className="h-28 rounded-xl ss-shimmer" style={{ opacity: 1 - i * 0.1 }} />
        ))}
      </div>
    </div>
  )
}
