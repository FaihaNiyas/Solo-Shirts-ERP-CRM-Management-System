'use client'

import { motion } from 'framer-motion'
import { cn } from '@/lib/utils'

interface DiffField {
  label: string
  key: string
  oldValue?: number | string | null
  newValue?: number | string | null
  unit?: string
}

interface VersionDiffViewProps {
  fields: DiffField[]
  oldLabel?: string
  newLabel?: string
  className?: string
}

export function VersionDiffView({
  fields,
  oldLabel = 'Previous',
  newLabel = 'Current',
  className,
}: VersionDiffViewProps) {
  const changed = fields.filter(
    (f) => f.oldValue !== f.newValue && (f.oldValue != null || f.newValue != null),
  )
  const unchanged = fields.filter(
    (f) => f.oldValue === f.newValue || (f.oldValue == null && f.newValue == null),
  )

  function formatVal(v: number | string | null | undefined, unit?: string) {
    if (v == null) return '—'
    return unit ? `${v} ${unit}` : String(v)
  }

  function delta(f: DiffField): string {
    if (typeof f.oldValue !== 'number' || typeof f.newValue !== 'number') return ''
    const d = f.newValue - f.oldValue
    return d > 0 ? `+${d.toFixed(1)}` : d.toFixed(1)
  }

  return (
    <div className={cn('space-y-4', className)}>
      {/* Column headers */}
      <div className="grid grid-cols-[1fr_1fr_1fr] gap-2 px-3 py-2 bg-[var(--color-surface-alt)] rounded-lg">
        <span className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">
          Field
        </span>
        <span className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide text-center">
          {oldLabel}
        </span>
        <span className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide text-center">
          {newLabel}
        </span>
      </div>

      {/* Changed fields */}
      {changed.length > 0 && (
        <div className="space-y-1">
          <p className="text-[11px] font-semibold text-[var(--color-text-muted)] uppercase tracking-wide px-1">
            Changed ({changed.length})
          </p>
          {changed.map((f, i) => (
            <motion.div
              key={f.key}
              initial={{ backgroundColor: 'rgba(217, 119, 6, 0.15)' }}
              animate={{ backgroundColor: 'rgba(217, 119, 6, 0)' }}
              transition={{ duration: 1.2, delay: i * 0.05 }}
              className="grid grid-cols-[1fr_1fr_1fr] gap-2 px-3 py-2.5 rounded-lg border-l-2 border-[var(--color-brand)]"
            >
              <span className="text-xs font-medium text-[var(--color-text-primary)]">
                {f.label}
              </span>
              <span className="text-xs text-center text-[var(--color-text-muted)] line-through">
                {formatVal(f.oldValue, f.unit)}
              </span>
              <span className="text-xs text-center font-semibold text-[var(--color-brand-dark)]">
                {formatVal(f.newValue, f.unit)}
                {delta(f) && (
                  <span className="ml-1 text-[10px] text-[var(--color-brand)]">({delta(f)})</span>
                )}
              </span>
            </motion.div>
          ))}
        </div>
      )}

      {/* Unchanged fields */}
      {unchanged.length > 0 && (
        <div className="space-y-1">
          <p className="text-[11px] font-semibold text-[var(--color-text-muted)] uppercase tracking-wide px-1">
            Unchanged ({unchanged.length})
          </p>
          {unchanged.map((f) => (
            <div
              key={f.key}
              className="grid grid-cols-[1fr_1fr_1fr] gap-2 px-3 py-2 rounded-lg"
            >
              <span className="text-xs text-[var(--color-text-muted)]">{f.label}</span>
              <span className="text-xs text-center text-[var(--color-text-muted)]">
                {formatVal(f.oldValue, f.unit)}
              </span>
              <span className="text-xs text-center text-[var(--color-text-muted)]">
                {formatVal(f.newValue, f.unit)}
              </span>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
