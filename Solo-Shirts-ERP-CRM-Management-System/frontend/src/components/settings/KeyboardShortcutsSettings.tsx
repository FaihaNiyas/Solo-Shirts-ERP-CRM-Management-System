'use client'

import { usePreferences } from '@/lib/preferences/PreferencesContext'
import { ShortcutKey } from '@/components/shortcuts/ShortcutKey'
import { Switch } from '@/components/ui/switch'
import { NAV_SHORTCUTS, WIZARD_SHORTCUTS } from '@/lib/shortcuts/config'

/**
 * Settings → Preferences section: the Enable Keyboard Shortcuts toggle plus the
 * full shortcut reference. OFF by default; the choice persists per browser/user.
 */
export function KeyboardShortcutsSettings() {
  const { preferences, updatePreference } = usePreferences()
  const enabled = preferences.keyboardShortcutsEnabled

  const rows: { keys: string[]; label: string }[] = [
    ...NAV_SHORTCUTS.map((s) => ({ keys: s.keys, label: s.label })),
    ...WIZARD_SHORTCUTS,
  ]

  return (
    <section className="space-y-3">
      <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">Keyboard Shortcuts</h3>

      <div className="flex items-center gap-3">
        <Switch
          label="Enable Keyboard Shortcuts"
          checked={enabled}
          onChange={() => updatePreference('keyboardShortcutsEnabled', !enabled)}
        />
        <span className="text-sm text-[var(--color-text-primary)]">Enable Keyboard Shortcuts</span>
      </div>

      <p className="text-xs text-[var(--color-text-muted)]">
        Use Alt+1–5 and Ctrl shortcuts for faster Front Desk navigation. Shortcuts are disabled while
        typing, and only work for actions you have access to.
      </p>

      <div className="rounded-xl border border-[var(--color-border)] overflow-hidden max-w-md">
        {rows.map((r) => (
          <div
            key={r.label}
            className="flex items-center justify-between gap-4 px-3 py-2 border-b border-[var(--color-border)] last:border-0"
          >
            <span className="text-sm text-[var(--color-text-secondary)]">{r.label}</span>
            <ShortcutKey keys={r.keys} muted={!enabled} />
          </div>
        ))}
      </div>
    </section>
  )
}
