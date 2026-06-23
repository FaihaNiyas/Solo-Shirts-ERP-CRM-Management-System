// Phase 1 draft persistence — localStorage fallback only.
//
// TODO(phase-2): Move drafts server-side so a counter can resume a draft from
// any terminal. Suggested API:
//   POST  /api/v1/order-drafts            → create
//   PATCH /api/v1/order-drafts/{id}       → autosave
//   GET   /api/v1/order-drafts            → list (dashboard widget)
//   GET   /api/v1/order-drafts/{id}       → resume
//   POST  /api/v1/order-drafts/{id}/promote → confirm into a real order
// Until then we keep a SINGLE active draft per user in localStorage.

import type { WizardSnapshot } from './types'

const PREFIX = 'ss_fd_draft:v1'

function key(userId: number | undefined): string {
  return `${PREFIX}:${userId ?? 'anon'}`
}

export function loadDraft(userId: number | undefined): WizardSnapshot | null {
  if (typeof window === 'undefined') return null
  try {
    const raw = localStorage.getItem(key(userId))
    if (!raw) return null
    const parsed = JSON.parse(raw) as WizardSnapshot
    if (parsed?.version !== 1) return null
    return parsed
  } catch {
    return null
  }
}

export function saveDraft(userId: number | undefined, snapshot: WizardSnapshot): void {
  if (typeof window === 'undefined') return
  try {
    localStorage.setItem(key(userId), JSON.stringify(snapshot))
  } catch {
    /* quota / serialization issues are non-fatal for a draft */
  }
}

export function clearDraft(userId: number | undefined): void {
  if (typeof window === 'undefined') return
  try {
    localStorage.removeItem(key(userId))
  } catch {
    /* ignore */
  }
}

export function hasDraft(userId: number | undefined): boolean {
  return loadDraft(userId) !== null
}
