// Production priority presentation (Kanban). Mirrors the backend Order priority
// enum: normal | high | urgent. 'normal' is the default and renders without a chip.

export type Priority = 'normal' | 'high' | 'urgent'

export const PRIORITY_LABELS: Record<Priority, string> = {
  normal: 'Normal',
  high: 'High',
  urgent: 'Urgent',
}

export const PRIORITY_CHIP: Record<Priority, string> = {
  normal: 'bg-[var(--color-surface-alt)] text-[var(--color-text-muted)]',
  high: 'bg-amber-50 text-amber-700',
  urgent: 'bg-red-50 text-red-600',
}

export function priorityLabel(p?: string | null): string {
  return PRIORITY_LABELS[(p as Priority) ?? 'normal'] ?? 'Normal'
}

/** Higher = more urgent. Used to sort cards within a column. */
export function priorityWeight(p?: string | null): number {
  return p === 'urgent' ? 2 : p === 'high' ? 1 : 0
}
