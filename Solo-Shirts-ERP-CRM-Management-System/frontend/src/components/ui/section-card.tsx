import { cn } from '@/lib/utils'

interface SectionCardProps {
  title?: string
  description?: string
  headerActions?: React.ReactNode
  footer?: React.ReactNode
  children: React.ReactNode
  className?: string
  noPadding?: boolean
  variant?: 'default' | 'overdue' | 'rework'
}

export function SectionCard({
  title,
  description,
  headerActions,
  footer,
  children,
  className,
  noPadding = false,
  variant = 'default',
}: SectionCardProps) {
  return (
    <div
      className={cn(
        'bg-white rounded-xl border border-[var(--color-border)]',
        variant === 'overdue' && 'border-l-4 border-l-red-400',
        variant === 'rework' && 'border-l-4 border-l-amber-400',
        className,
      )}
    >
      {(title || headerActions) && (
        <div className="flex items-start justify-between px-4 py-3.5 border-b border-[var(--color-border)]">
          <div>
            {title && (
              <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">{title}</h3>
            )}
            {description && (
              <p className="mt-0.5 text-xs text-[var(--color-text-muted)]">{description}</p>
            )}
          </div>
          {headerActions && (
            <div className="flex items-center gap-2 shrink-0 ml-4">{headerActions}</div>
          )}
        </div>
      )}
      <div className={cn(!noPadding && 'p-4')}>{children}</div>
      {footer && (
        <div className="px-4 py-3 border-t border-[var(--color-border)] bg-[var(--color-surface-alt)] rounded-b-xl">
          {footer}
        </div>
      )}
    </div>
  )
}
