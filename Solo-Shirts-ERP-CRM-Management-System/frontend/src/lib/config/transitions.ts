// Each forward production move maps to the permission the backend ProductionPolicy
// requires. A user without it should never see the move (button OR drag target) —
// the backend would only reject it. Single source of truth shared by the card's
// Advance buttons and the Supervisor Board's drag-and-drop legality check.
export const TRANSITION_PERMISSION: Record<string, string> = {
  FabricAllocated: 'production.transition.fabric_allocated',
  Cutting: 'production.transition.cutting',
  Tailoring: 'production.transition.tailoring',
  KajaButton: 'production.transition.kaja',
  Finishing: 'production.transition.finishing',
  QC: 'production.transition.qc',
  Rework: 'production.transition.rework',
  Packing: 'production.transition.packing',
  ReadyForDelivery: 'production.transition.ready_for_delivery',
  Delivered: 'production.transition.delivered',
  Cancelled: 'production.transition.cancel',
}

/**
 * The subset of an item's backend-provided allowed_transitions (PascalCase) that
 * the current user may actually perform. `can` is the usePermission predicate.
 * Used to render Advance buttons and to decide which board columns are legal
 * drop targets — drag never bypasses this, and the backend re-checks regardless.
 */
export function permittedTransitions(
  allowed: string[] | undefined,
  can: (perm: string) => boolean,
): string[] {
  return (allowed ?? []).filter((t) => {
    const perm = TRANSITION_PERMISSION[t]
    return perm ? can(perm) : true
  })
}
