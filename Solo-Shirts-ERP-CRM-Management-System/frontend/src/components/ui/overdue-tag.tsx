import { Clock } from 'lucide-react'
import { cn } from '@/lib/utils'

interface OverdueTagProps {
  days: number
  className?: string
}

export function OverdueTag({ days, className }: OverdueTagProps) {
  const severe = days > 7
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
        severe
          ? 'bg-red-100 text-red-700'
          : 'bg-amber-100 text-amber-700',
        className,
      )}
    >
      <Clock size={10} strokeWidth={2} />
      {days}d overdue
    </span>
  )
}
