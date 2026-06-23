import { cn } from '@/lib/utils'

interface InfoItem {
  label: string
  value: React.ReactNode
  span?: 1 | 2
}

interface InfoGridProps {
  items: InfoItem[]
  cols?: 2 | 3 | 4
  className?: string
}

export function InfoGrid({ items, cols = 2, className }: InfoGridProps) {
  const gridCols = {
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-3',
    4: 'grid-cols-2 sm:grid-cols-4',
  }[cols]

  return (
    <dl className={cn('grid gap-x-8 gap-y-4', gridCols, className)}>
      {items.map((item, i) => (
        <div
          key={i}
          className={cn(item.span === 2 && 'sm:col-span-2')}
        >
          <dt className="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wide mb-1">
            {item.label}
          </dt>
          <dd className="text-sm font-medium text-[var(--color-text-primary)]">
            {item.value ?? <span className="text-[var(--color-text-muted)]">—</span>}
          </dd>
        </div>
      ))}
    </dl>
  )
}
