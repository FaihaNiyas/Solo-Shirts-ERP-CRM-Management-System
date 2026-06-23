// Single canonical mapper for production states. Mirrors the backend
// OrderProgressSummary::STATE_LABELS so labels match everywhere, and resolves
// the StatusBadge colour variant. Accepts snake_case (the API's canonical form)
// or the legacy PascalCase variant keys (passed through unchanged).

/** snake_case state → human label (matches backend OrderProgressSummary). */
export const PRODUCTION_STATE_LABELS: Record<string, string> = {
  draft: 'Draft',
  fabric_allocated: 'Fabric Ready',
  cutting: 'Cutting',
  tailoring: 'Tailoring',
  kaja_button: 'Kaja / Button',
  finishing: 'Finishing',
  qc: 'QC',
  rework: 'Rework',
  packing: 'Packing',
  ready_for_delivery: 'Ready for Pickup',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
}

/** snake_case state → PascalCase StatusBadge variant key (colour). */
const PRODUCTION_STATE_VARIANTS: Record<string, string> = {
  draft: 'Draft',
  fabric_allocated: 'FabricAllocated',
  cutting: 'Cutting',
  tailoring: 'Tailoring',
  kaja_button: 'KajaButton',
  finishing: 'Finishing',
  qc: 'QC',
  rework: 'Rework',
  packing: 'Packing',
  ready_for_delivery: 'ReadyForDelivery',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
}

export function productionStateLabel(state: string | null | undefined): string {
  if (!state) return '—'
  return (
    PRODUCTION_STATE_LABELS[state] ??
    state.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
  )
}

/** Resolve the StatusBadge colour variant; passes through known PascalCase keys. */
export function productionStateVariant(state: string | null | undefined): string {
  if (!state) return 'neutral'
  return PRODUCTION_STATE_VARIANTS[state] ?? state
}
