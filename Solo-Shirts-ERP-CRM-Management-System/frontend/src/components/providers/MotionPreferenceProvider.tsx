'use client'

import { MotionConfig } from 'framer-motion'
import { usePreferences } from '@/lib/preferences/PreferencesContext'

/**
 * Feeds the in-app "Reduce motion" preference into framer-motion so JS-driven
 * animations (drawers, modals, page transitions) actually honour the toggle —
 * the CSS media query alone can't reach framer. When the preference is off we
 * fall back to "user", which respects the OS prefers-reduced-motion setting.
 */
export function MotionPreferenceProvider({ children }: { children: React.ReactNode }) {
  const reduced = usePreferences().preferences.reducedMotion
  return <MotionConfig reducedMotion={reduced ? 'always' : 'user'}>{children}</MotionConfig>
}
