export const STAGE_LABELS: Record<string, string> = {
  Draft: 'Draft',
  FabricAllocated: 'Fabric Ready',
  Cutting: 'Cutting',
  Tailoring: 'Tailoring',
  KajaButton: 'Kaja & Button',
  Finishing: 'Finishing',
  QC: 'QC',
  Packing: 'Packing',
  ReadyForDelivery: 'Ready for Delivery',
  Delivered: 'Delivered',
  Rework: 'Rework',
  Cancelled: 'Cancelled',
}

export function getStageLabel(state: string): string {
  return STAGE_LABELS[state] ?? state
}
