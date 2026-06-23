'use client'

import { useEffect, useState } from 'react'
import { Sun, Moon, Monitor } from 'lucide-react'
import { usePreferences } from '@/lib/preferences/PreferencesContext'
import { usePermission, ROLES } from '@/lib/auth/permissions'
import { cn } from '@/lib/utils'
import { PageHeader } from '@/components/ui/page-header'

const BRAND_PRESETS = [
  '#D97706',
  '#2563EB',
  '#7C3AED',
  '#DC2626',
  '#16A34A',
  '#0891B2',
  '#EA580C',
  '#4F46E5',
]

const THEME_OPTIONS = [
  { value: 'light' as const, label: 'Light', icon: Sun },
  { value: 'dark' as const, label: 'Dark', icon: Moon },
  { value: 'system' as const, label: 'System', icon: Monitor },
]

const DENSITY_OPTIONS = [
  { value: 'compact' as const, label: 'Compact', desc: 'More rows visible' },
  { value: 'default' as const, label: 'Default', desc: 'Balanced spacing' },
  { value: 'comfortable' as const, label: 'Comfortable', desc: 'Extra breathing room' },
]

const RADIUS_OPTIONS = [
  { value: 'sharp' as const, label: 'Sharp', cls: 'rounded-none' },
  { value: 'rounded' as const, label: 'Rounded', cls: 'rounded-lg' },
  { value: 'soft' as const, label: 'Soft', cls: 'rounded-2xl' },
]

export default function AppearancePage() {
  const { preferences, updatePreference, resetPreferences } = usePreferences()
  const { is } = usePermission()
  const canChangeBrand = is(ROLES.OWNER) || is('admin')
  const [localBrand, setLocalBrand] = useState(preferences.brandColor)

  // Keep the local swatch in sync when preferences load or are reset elsewhere.
  useEffect(() => {
    setLocalBrand(preferences.brandColor)
  }, [preferences.brandColor])

  return (
    <div className="space-y-8 max-w-2xl">
      <PageHeader title="Appearance" subtitle="Customise how the app looks" />

      <section className="space-y-3">
        <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">Theme</h3>
        <div className="flex gap-3">
          {THEME_OPTIONS.map(({ value, label, icon: Icon }) => (
            <button
              key={value}
              onClick={() => updatePreference('theme', value)}
              className={cn(
                'flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all flex-1',
                preferences.theme === value
                  ? 'border-[var(--color-brand)] bg-[var(--color-brand-light)]'
                  : 'border-[var(--color-border)] bg-white hover:border-[var(--color-border-mid)]',
              )}
            >
              <Icon
                size={20}
                strokeWidth={1.75}
                className={
                  preferences.theme === value
                    ? 'text-[var(--color-brand)]'
                    : 'text-[var(--color-text-muted)]'
                }
              />
              <span className="text-xs font-medium text-[var(--color-text-primary)]">{label}</span>
            </button>
          ))}
        </div>
      </section>

      {canChangeBrand && (
        <section className="space-y-3">
          <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">Brand Colour</h3>
          <div className="flex items-center gap-3">
            <input
              type="color"
              value={localBrand}
              onChange={(e) => setLocalBrand(e.target.value)}
              className="w-10 h-10 rounded-lg border border-[var(--color-border)] cursor-pointer"
            />
            <input
              type="text"
              value={localBrand}
              onChange={(e) => setLocalBrand(e.target.value)}
              className="w-28 h-9 px-3 text-sm font-mono border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </div>
          <div className="flex gap-2">
            {BRAND_PRESETS.map((c) => (
              <button
                key={c}
                onClick={() => setLocalBrand(c)}
                className={cn(
                  'w-7 h-7 rounded-full border-2 transition-all',
                  localBrand === c ? 'border-[var(--color-text-primary)] scale-110' : 'border-transparent',
                )}
                style={{ backgroundColor: c }}
              />
            ))}
          </div>
          <div className="flex gap-2">
            <button
              onClick={() => updatePreference('brandColor', localBrand)}
              className="px-4 py-2 text-sm bg-[var(--color-brand)] text-white rounded-lg font-medium hover:bg-[var(--color-brand-dark)] transition-colors"
            >
              Apply globally
            </button>
            <button
              onClick={() => {
                setLocalBrand('#D97706')
                updatePreference('brandColor', '#D97706')
              }}
              className="px-4 py-2 text-sm border border-[var(--color-border)] rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Reset
            </button>
          </div>
        </section>
      )}

      <section className="space-y-3">
        <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">Data Density</h3>
        <div className="flex gap-3">
          {DENSITY_OPTIONS.map(({ value, label, desc }) => (
            <button
              key={value}
              onClick={() => updatePreference('dataDensity', value)}
              className={cn(
                'flex flex-col items-start gap-1 p-4 rounded-xl border-2 transition-all flex-1',
                preferences.dataDensity === value
                  ? 'border-[var(--color-brand)] bg-[var(--color-brand-light)]'
                  : 'border-[var(--color-border)] bg-white hover:border-[var(--color-border-mid)]',
              )}
            >
              <span className="text-sm font-medium text-[var(--color-text-primary)]">{label}</span>
              <span className="text-xs text-[var(--color-text-muted)]">{desc}</span>
            </button>
          ))}
        </div>
      </section>

      <section className="space-y-3">
        <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">Corner Style</h3>
        <div className="flex gap-3">
          {RADIUS_OPTIONS.map(({ value, label, cls }) => (
            <button
              key={value}
              onClick={() => updatePreference('borderRadius', value)}
              className={cn(
                'flex flex-col items-center gap-3 p-4 border-2 transition-all flex-1',
                preferences.borderRadius === value
                  ? 'border-[var(--color-brand)] bg-[var(--color-brand-light)]'
                  : 'border-[var(--color-border)] bg-white hover:border-[var(--color-border-mid)]',
                cls,
              )}
            >
              <div className={cn('w-8 h-8 border-2 border-[var(--color-border-mid)]', cls)} />
              <span className="text-xs font-medium text-[var(--color-text-primary)]">{label}</span>
            </button>
          ))}
        </div>
      </section>

      <div className="flex gap-2 pt-2">
        <button
          onClick={resetPreferences}
          className="px-4 py-2 text-sm border border-[var(--color-border)] rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
        >
          Reset to defaults
        </button>
      </div>
    </div>
  )
}
