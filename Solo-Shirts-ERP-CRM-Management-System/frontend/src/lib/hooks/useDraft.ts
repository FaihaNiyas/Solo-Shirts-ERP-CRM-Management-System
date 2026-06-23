'use client'

import { useState, useEffect, useRef, useCallback } from 'react'

interface DraftHookReturn<T> {
  draft: T | null
  saveDraft: (data: T) => void
  clearDraft: () => void
  restorePrompted: boolean
  dismissRestore: () => void
}

export function useDraft<T>(moduleKey: string, userId: number | null): DraftHookReturn<T> {
  const storageKey = userId ? `drafts:${userId}:${moduleKey}` : null
  const [draft, setDraft] = useState<T | null>(null)
  const [restorePrompted, setRestorePrompted] = useState(false)
  const timerRef = useRef<ReturnType<typeof setTimeout>>(undefined)

  useEffect(() => {
    if (!storageKey) return
    try {
      const raw = localStorage.getItem(storageKey)
      if (raw) {
        const parsed = JSON.parse(raw) as T
        setDraft(parsed)
        setRestorePrompted(true)
      }
    } catch {
      // ignore
    }
  }, [storageKey])

  const saveDraft = useCallback(
    (data: T) => {
      if (!storageKey) return
      clearTimeout(timerRef.current)
      timerRef.current = setTimeout(() => {
        try {
          localStorage.setItem(storageKey, JSON.stringify(data))
          setDraft(data)
        } catch {
          // ignore storage quota errors
        }
      }, 2000)
    },
    [storageKey],
  )

  const clearDraft = useCallback(() => {
    if (!storageKey) return
    clearTimeout(timerRef.current)
    localStorage.removeItem(storageKey)
    setDraft(null)
    setRestorePrompted(false)
  }, [storageKey])

  const dismissRestore = useCallback(() => {
    setRestorePrompted(false)
  }, [])

  return { draft, saveDraft, clearDraft, restorePrompted, dismissRestore }
}
