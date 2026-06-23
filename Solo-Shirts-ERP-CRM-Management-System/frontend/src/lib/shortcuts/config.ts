// Front Desk keyboard-shortcut definitions — the single source of truth shared
// by the global listener, the visible key badges and the Settings reference list.

export interface NavShortcut {
  /** Display chips, e.g. ['Alt', '1']. */
  keys: string[]
  /** Top-row digit (KeyboardEvent.code === `Digit${digit}`) that fires it while Alt is held. */
  digit: string
  label: string
  href: string
  permission: string // gate — both the action and its badge require this
}

/**
 * Global Front Desk navigation shortcuts. Bound to Alt+1..5 (not bare F1–F5):
 * the function keys collide with the browser's Refresh/Find/Help and screen-reader
 * commands, whereas Alt+digit is free. Matched on KeyboardEvent.code so the binding
 * survives layouts where Alt/Option rewrites the produced character (e.g. macOS).
 */
export const NAV_SHORTCUTS: NavShortcut[] = [
  { keys: ['Alt', '1'], digit: '1', label: 'New Order', href: '/front-desk/new?new=1', permission: 'orders.create' },
  { keys: ['Alt', '2'], digit: '2', label: 'Drafts', href: '/front-desk/drafts', permission: 'orders.create' },
  { keys: ['Alt', '3'], digit: '3', label: 'Order Lookup', href: '/front-desk/lookup', permission: 'orders.lookup' },
  { keys: ['Alt', '4'], digit: '4', label: 'Ready Rack', href: '/front-desk/ready-rack', permission: 'orders.lookup' },
  { keys: ['Alt', '5'], digit: '5', label: 'Alterations', href: '/front-desk/alterations', permission: 'alterations.view' },
]

/** Wizard-only shortcuts — handled inside FrontDeskWizard (need wizard context). */
export const WIZARD_SHORTCUTS: { keys: string[]; label: string }[] = [
  { keys: ['Ctrl', 'S'], label: 'Save Draft & Pause' },
  { keys: ['Ctrl', 'Enter'], label: 'Next Step / Continue' },
  { keys: ['Esc'], label: 'Close modal / drawer' },
]
