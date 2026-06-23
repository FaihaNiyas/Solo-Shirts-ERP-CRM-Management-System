'use client'

import { useState } from 'react'
import { Keyboard } from 'lucide-react'
import { usePreferences } from '@/lib/preferences/PreferencesContext'
import { PageHeader } from '@/components/ui/page-header'
import { ModalDialog } from '@/components/ui/modal-dialog'
import { Switch } from '@/components/ui/switch'

const SHORTCUTS = [
  { key: 'Ctrl+K', action: 'Open global search' },
  { key: 'Ctrl+F', action: 'Focus customer search (Front Desk)' },
  { key: 'Ctrl+N', action: 'New customer' },
  { key: 'Ctrl+Enter', action: 'Confirm order' },
  { key: 'Ctrl+S', action: 'Save draft' },
  { key: 'Escape', action: 'Close drawer / dialog' },
]

export default function AccessibilityPage() {
  const { preferences, updatePreference } = usePreferences()
  const [showShortcuts, setShowShortcuts] = useState(false)

  return (
    <div className="space-y-8 max-w-2xl">
      <PageHeader title="Accessibility" subtitle="Motion, contrast and input preferences" />

      <div className="space-y-0 divide-y divide-[var(--color-border)]">
        <div className="flex items-center justify-between py-4">
          <div>
            <p className="text-sm font-medium text-[var(--color-text-primary)]">Reduce motion</p>
            <p className="text-xs text-[var(--color-text-muted)]">
              Disable animations and transitions
            </p>
          </div>
          <Switch
            label="Reduce motion"
            checked={preferences.reducedMotion}
            onChange={() => updatePreference('reducedMotion', !preferences.reducedMotion)}
          />
        </div>

        <div className="flex items-center justify-between py-4">
          <div>
            <p className="text-sm font-medium text-[var(--color-text-primary)]">Sound feedback</p>
            <p className="text-xs text-[var(--color-text-muted)]">
              Play beep on scan success / failure
            </p>
          </div>
          <Switch
            label="Sound feedback"
            checked={preferences.soundFeedback}
            onChange={() => updatePreference('soundFeedback', !preferences.soundFeedback)}
          />
        </div>

        <div className="flex items-center justify-between py-4">
          <div>
            <p className="text-sm font-medium text-[var(--color-text-primary)]">Larger text</p>
            <p className="text-xs text-[var(--color-text-muted)]">Increase base font size</p>
          </div>
          <Switch
            label="Larger text"
            checked={preferences.fontSize === 'large'}
            onChange={() =>
              updatePreference('fontSize', preferences.fontSize === 'large' ? 'default' : 'large')
            }
          />
        </div>
      </div>

      <button
        onClick={() => setShowShortcuts(true)}
        className="flex items-center gap-2 text-sm text-[var(--color-brand)] hover:underline"
      >
        <Keyboard size={15} strokeWidth={1.75} />
        View keyboard shortcuts
      </button>

      <ModalDialog
        open={showShortcuts}
        onClose={() => setShowShortcuts(false)}
        title="Keyboard Shortcuts"
      >
        <div className="space-y-0 divide-y divide-[var(--color-border)]">
          {SHORTCUTS.map(({ key, action }) => (
            <div key={key} className="flex items-center justify-between py-3">
              <span className="text-sm text-[var(--color-text-secondary)]">{action}</span>
              <kbd className="px-2 py-0.5 text-xs font-mono bg-[var(--color-surface-alt)] border border-[var(--color-border)] rounded">
                {key}
              </kbd>
            </div>
          ))}
        </div>
      </ModalDialog>
    </div>
  )
}
