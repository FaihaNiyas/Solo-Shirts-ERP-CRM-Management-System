'use client'

import { useEffect, useState } from 'react'
import { Cloud, CloudOff } from 'lucide-react'
import { useWizard } from './WizardContext'

/** "Draft saved · 2/10 complete" indicator driven by autosave + progress. */
export function DraftStatusIndicator() {
  const { savedAt, progressDone, progressTotal } = useWizard()
  const [time, setTime] = useState<string>('')

  // Format the saved time only after mount to avoid SSR/locale mismatch.
  useEffect(() => {
    if (!savedAt) {
      setTime('')
      return
    }
    setTime(new Date(savedAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }))
  }, [savedAt])

  return (
    <div className="inline-flex items-center gap-2 text-xs text-[var(--color-text-muted)]">
      {savedAt ? (
        <>
          <Cloud size={13} strokeWidth={1.75} className="text-[var(--color-success)]" />
          <span>Draft saved{time ? ` · ${time}` : ''}</span>
        </>
      ) : (
        <>
          <CloudOff size={13} strokeWidth={1.75} />
          <span>Not saved yet</span>
        </>
      )}
      <span className="text-[var(--color-border-mid)]" aria-hidden>·</span>
      <span className="font-medium text-[var(--color-text-secondary)]">
        {progressDone}/{progressTotal} complete
      </span>
    </div>
  )
}
