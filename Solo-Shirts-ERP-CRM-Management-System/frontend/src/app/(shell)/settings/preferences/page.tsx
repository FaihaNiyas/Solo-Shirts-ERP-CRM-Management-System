'use client'

import { usePreferences } from '@/lib/preferences/PreferencesContext'
import { cn } from '@/lib/utils'
import { PageHeader } from '@/components/ui/page-header'
import { Switch } from '@/components/ui/switch'
import { KeyboardShortcutsSettings } from '@/components/settings/KeyboardShortcutsSettings'

export default function PreferencesPage() {
  const { preferences, updatePreference } = usePreferences()

  return (
    <div className="space-y-8 max-w-2xl">
      <PageHeader title="Preferences" subtitle="Localisation and workflow settings" />

      <section className="space-y-3">
        <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">Measurement Unit</h3>
        <div className="inline-flex rounded-lg border border-[var(--color-border)] overflow-hidden">
          {(['cm', 'inches'] as const).map((unit) => (
            <button
              key={unit}
              onClick={() => updatePreference('measurementUnit', unit)}
              className={cn(
                'px-6 py-2 text-sm font-medium transition-colors',
                preferences.measurementUnit === unit
                  ? 'bg-[var(--color-brand)] text-white'
                  : 'bg-white text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]',
              )}
            >
              {unit === 'cm' ? 'Centimetres (cm)' : 'Inches (in)'}
            </button>
          ))}
        </div>
      </section>

      <section className="space-y-3">
        <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">Date Format</h3>
        <div className="space-y-2">
          {(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'] as const).map((fmt) => {
            const examples: Record<string, string> = {
              'DD/MM/YYYY': '15/06/2025',
              'MM/DD/YYYY': '06/15/2025',
              'YYYY-MM-DD': '2025-06-15',
            }
            return (
              <label key={fmt} className="flex items-center gap-3 cursor-pointer">
                <input
                  type="radio"
                  checked={preferences.dateFormat === fmt}
                  onChange={() => updatePreference('dateFormat', fmt)}
                  className="accent-[var(--color-brand)]"
                />
                <span className="text-sm text-[var(--color-text-primary)]">{fmt}</span>
                <span className="text-sm text-[var(--color-text-muted)] font-mono">
                  → {examples[fmt]}
                </span>
              </label>
            )
          })}
        </div>
      </section>

      <section className="space-y-3">
        <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">
          Customer Notification Channel
        </h3>
        <div className="space-y-2">
          {(['whatsapp', 'email', 'sms'] as const).map((ch) => (
            <label key={ch} className="flex items-center gap-3 cursor-pointer">
              <input
                type="radio"
                checked={preferences.notificationChannel === ch}
                onChange={() => updatePreference('notificationChannel', ch)}
                className="accent-[var(--color-brand)]"
              />
              <span className="text-sm text-[var(--color-text-primary)] capitalize">{ch}</span>
            </label>
          ))}
        </div>
      </section>

      <section className="space-y-3">
        <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">Auto-save Drafts</h3>
        <div className="flex items-center gap-3">
          <Switch
            label="Automatically save form drafts"
            checked={preferences.autoSaveDraft}
            onChange={() => updatePreference('autoSaveDraft', !preferences.autoSaveDraft)}
          />
          <span className="text-sm text-[var(--color-text-primary)]">
            Automatically save form drafts
          </span>
        </div>
      </section>

      <KeyboardShortcutsSettings />
    </div>
  )
}
