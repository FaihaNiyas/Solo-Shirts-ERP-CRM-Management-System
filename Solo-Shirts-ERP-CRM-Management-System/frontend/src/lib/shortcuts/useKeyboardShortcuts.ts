'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { usePermission } from '@/lib/auth/permissions'
import { usePreferences } from '@/lib/preferences/PreferencesContext'
import { NAV_SHORTCUTS } from './config'

/** Whether the Front Desk keyboard shortcuts are enabled (per browser/user). */
export function useShortcutsEnabled(): boolean {
  return usePreferences().preferences.keyboardShortcutsEnabled
}

/**
 * A shortcut must never fire while the user is typing. Covers native form
 * controls, contenteditable, and ARIA combobox/listbox widgets (measurement,
 * pricing, search and dropdown fields).
 */
export function isTypingTarget(target: EventTarget | null): boolean {
  if (!(target instanceof HTMLElement)) return false
  const tag = target.tagName
  if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return true
  if (target.isContentEditable) return true
  return target.closest('[contenteditable="true"],[role="combobox"],[role="listbox"],[role="textbox"]') !== null
}

/**
 * Registers the GLOBAL Front Desk navigation shortcuts (Alt+1..5). No-op unless the
 * preference is enabled. Each key is gated by the same permission that backs its
 * page, so a shortcut can never reach a module the user can't access — the server
 * remains the source of truth. Wizard-only combos (Ctrl+S / Ctrl+Enter) are bound
 * inside FrontDeskWizard, where the wizard handlers live.
 */
export function useKeyboardShortcuts(): void {
  const router = useRouter()
  const { can } = usePermission()
  const enabled = useShortcutsEnabled()

  useEffect(() => {
    if (!enabled) return

    function onKeyDown(e: KeyboardEvent): void {
      if (e.defaultPrevented) return
      // Alt+digit only — Alt (and only Alt) avoids hijacking Refresh/Find/Help.
      if (!e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return
      if (isTypingTarget(e.target)) return

      // Match on physical key (code) so Alt/Option rewriting the character (macOS)
      // does not break the binding.
      const shortcut = NAV_SHORTCUTS.find((s) => e.code === `Digit${s.digit}`)
      if (!shortcut) return
      // No permission → leave the browser/OS default for that combo intact.
      if (!can(shortcut.permission)) return

      e.preventDefault()
      router.push(shortcut.href)
    }

    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [enabled, can, router])
}
