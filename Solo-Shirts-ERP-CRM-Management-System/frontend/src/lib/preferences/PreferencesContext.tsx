'use client'

import React, { createContext, useContext, useEffect, useState, useCallback } from 'react'
import { defaultPreferences, PreferencesSchema, type Preferences } from './schema'
import { useAuthStore } from '@/lib/auth/store'

interface PreferencesContextValue {
  preferences: Preferences
  updatePreference: <K extends keyof Preferences>(key: K, value: Preferences[K]) => void
  resetPreferences: () => void
}

const PreferencesContext = createContext<PreferencesContextValue | null>(null)

function getStorageKey(userId?: number | null) {
  return userId ? `prefs:${userId}` : 'prefs:guest'
}

export function PreferencesProvider({ children }: { children: React.ReactNode }) {
  const user = useAuthStore((s) => s.user)
  const [preferences, setPreferences] = useState<Preferences>(defaultPreferences)

  useEffect(() => {
    const key = getStorageKey(user?.id)
    try {
      const raw = localStorage.getItem(key)
      if (raw) {
        const parsed = PreferencesSchema.safeParse(JSON.parse(raw))
        if (parsed.success) setPreferences(parsed.data)
      }
    } catch {
      // ignore
    }
  }, [user?.id])

  useEffect(() => {
    const root = document.documentElement

    // Theme — resolve "system" to the actual OS preference, and drive the
    // dark palette via the `.dark` class (which globals.css actually targets).
    const applyTheme = () => {
      const resolved =
        preferences.theme === 'system'
          ? window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light'
          : preferences.theme
      root.classList.toggle('dark', resolved === 'dark')
      root.setAttribute('data-theme', resolved)
    }
    applyTheme()

    // Keep following the OS while "system" is selected.
    let mq: MediaQueryList | null = null
    if (preferences.theme === 'system') {
      mq = window.matchMedia('(prefers-color-scheme: dark)')
      mq.addEventListener('change', applyTheme)
    }

    root.setAttribute('data-density', preferences.dataDensity)
    root.setAttribute('data-radius', preferences.borderRadius)
    if (preferences.reducedMotion) {
      root.setAttribute('data-reduced-motion', 'true')
    } else {
      root.removeAttribute('data-reduced-motion')
    }
    const scale =
      preferences.fontSize === 'small' ? '0.9' : preferences.fontSize === 'large' ? '1.1' : '1'
    root.style.setProperty('--font-scale', scale)

    // Brand — also derive the hover shade so buttons stay consistent.
    root.style.setProperty('--color-brand', preferences.brandColor)
    root.style.setProperty(
      '--color-brand-dark',
      `color-mix(in srgb, ${preferences.brandColor} 82%, #000)`,
    )

    return () => {
      if (mq) mq.removeEventListener('change', applyTheme)
    }
  }, [preferences])

  const updatePreference = useCallback(
    <K extends keyof Preferences>(key: K, value: Preferences[K]) => {
      setPreferences((prev) => {
        const next = { ...prev, [key]: value }
        try {
          localStorage.setItem(getStorageKey(user?.id), JSON.stringify(next))
        } catch {
          // ignore
        }
        return next
      })
    },
    [user?.id],
  )

  const resetPreferences = useCallback(() => {
    localStorage.removeItem(getStorageKey(user?.id))
    setPreferences(defaultPreferences)
  }, [user?.id])

  return (
    <PreferencesContext.Provider value={{ preferences, updatePreference, resetPreferences }}>
      {children}
    </PreferencesContext.Provider>
  )
}

export function usePreferences(): PreferencesContextValue {
  const ctx = useContext(PreferencesContext)
  if (!ctx) throw new Error('usePreferences must be used within PreferencesProvider')
  return ctx
}
