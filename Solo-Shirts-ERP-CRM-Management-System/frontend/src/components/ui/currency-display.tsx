import { cn } from '@/lib/utils'
import { formatINR } from '@/lib/utils'

interface CurrencyDisplayProps {
  amount: number | string | null | undefined
  size?: 'sm' | 'md' | 'lg'
  className?: string
  muted?: boolean
}

export function CurrencyDisplay({ amount, size = 'md', className, muted = false }: CurrencyDisplayProps) {
  const formatted = amount != null ? formatINR(Number(amount)) : '—'
  const sizeClass = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-2xl font-semibold',
  }[size]

  return (
    <span
      className={cn(
        'font-mono tabular-nums',
        sizeClass,
        muted ? 'text-[var(--color-text-muted)]' : 'text-[var(--color-text-primary)]',
        className,
      )}
    >
      {formatted}
    </span>
  )
}
