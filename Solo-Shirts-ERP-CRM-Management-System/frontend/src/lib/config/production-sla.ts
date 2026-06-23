const SLA_THRESHOLDS: Record<string, number> = {
  FabricAllocated: 1,
  Cutting: 1,
  Tailoring: 3,
  KajaButton: 1,
  Finishing: 1,
  QC: 1,
  Rework: 2,
  Packing: 1,
}

export function getSlaThreshold(state: string): number {
  return SLA_THRESHOLDS[state] ?? 0
}

export function isOverdue(state: string, enteredAt: string): boolean {
  const threshold = getSlaThreshold(state)
  if (threshold === 0) return false
  const diffDays = (Date.now() - new Date(enteredAt).getTime()) / (1000 * 60 * 60 * 24)
  return diffDays > threshold
}

export { SLA_THRESHOLDS }
