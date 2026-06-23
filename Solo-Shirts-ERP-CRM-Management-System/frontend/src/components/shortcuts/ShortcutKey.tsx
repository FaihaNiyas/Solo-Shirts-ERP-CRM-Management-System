'use client'

import { cn } from '@/lib/utils'

/**
 * A small, subtle key badge, e.g. <ShortcutKey keys={['F1']} /> or
 * <ShortcutKey keys={['Ctrl', 'S']} />. When `muted`, it dims and explains that
 * shortcuts are off — used everywhere shortcuts are advertised so the UI stays
 * consistent across nav, dashboard, wizard and settings.
 */
export function ShortcutKey({
  keys,
  muted = false,
  className,
}: {
  keys: string[]
  muted?: boolean
  className?: string
}) {
  return (
    <span
      className={cn('inline-flex items-center gap-0.5', className)}
      title={muted ? 'Enable keyboard shortcuts in Settings → Preferences' : undefined}
      aria-hidden
    >
      {keys.map((k, i) => (
        <kbd
          key={i}
          className={cn(
            'inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded border text-[10px] font-medium leading-none font-sans',
            muted
              ? 'border-[var(--color-border)] bg-[var(--color-surface-alt)] text-[var(--color-text-muted)] opacity-60'
              : 'border-[var(--color-border-mid)] bg-white text-[var(--color-text-secondary)]',
          )}
        >
          {k}
        </kbd>
      ))}
    </span>
  )
}
