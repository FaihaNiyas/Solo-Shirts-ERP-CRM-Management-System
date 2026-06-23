'use client'

import { cn } from '@/lib/utils'

/**
 * Accessible on/off switch. Renders a real <button role="switch">, so it is
 * focusable and toggles with Space/Enter natively — no manual key handling
 * needed. Use everywhere a settings toggle is shown; pass `label` so screen
 * readers announce what is being switched.
 */
export function Switch({
  checked,
  onChange,
  label,
  id,
  disabled = false,
  className,
}: {
  checked: boolean
  onChange: () => void
  /** Accessible name announced by assistive tech (e.g. "Reduce motion"). */
  label?: string
  id?: string
  disabled?: boolean
  className?: string
}) {
  return (
    <button
      type="button"
      role="switch"
      id={id}
      aria-checked={checked}
      aria-label={label}
      disabled={disabled}
      onClick={onChange}
      className={cn(
        'relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand)] focus-visible:ring-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-50',
        checked ? 'bg-[var(--color-brand)]' : 'bg-[var(--color-border-mid)]',
        className,
      )}
    >
      <span
        className={cn(
          'pointer-events-none absolute top-1 h-4 w-4 rounded-full bg-white shadow-sm transition-transform',
          checked ? 'translate-x-6' : 'translate-x-1',
        )}
      />
    </button>
  )
}
