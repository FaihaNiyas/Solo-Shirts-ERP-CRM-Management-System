'use client'

import { useKeyboardShortcuts } from '@/lib/shortcuts/useKeyboardShortcuts'

/**
 * Registers the global Front Desk navigation shortcuts (F1–F5) for the shell.
 * Mounted inside the authenticated shell so it has router + permission context.
 * It renders nothing of its own — wizard combos (Ctrl+S / Ctrl+Enter) live in the
 * wizard, and Esc is handled by the Radix dialog/drawer primitives.
 */
export function KeyboardShortcutProvider({ children }: { children: React.ReactNode }) {
  useKeyboardShortcuts()
  return <>{children}</>
}
