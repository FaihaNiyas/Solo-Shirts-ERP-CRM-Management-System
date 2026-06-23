// Route-level loading UI for /tailoring — assignment list.
export default function TailoringLoading() {
  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <div className="h-7 w-32 rounded-lg ss-shimmer" />
        <div className="h-3.5 w-56 rounded ss-shimmer" />
      </div>
      <div className="space-y-3">
        {[...Array(6)].map((_, i) => (
          <div key={i} className="h-20 rounded-xl ss-shimmer" style={{ opacity: 1 - i * 0.12 }} />
        ))}
      </div>
    </div>
  )
}
