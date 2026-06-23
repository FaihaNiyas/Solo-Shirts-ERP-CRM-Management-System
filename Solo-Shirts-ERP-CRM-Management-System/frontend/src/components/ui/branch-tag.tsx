import { Building2 } from 'lucide-react'
import { cn } from '@/lib/utils'

interface BranchTagProps {
  name: string
  code?: string
  className?: string
}

export function BranchTag({ name, code, className }: BranchTagProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5',
        'text-xs font-medium text-[var(--color-text-secondary)]',
        'bg-[var(--color-surface-alt)] border border-[var(--color-border)]',
        className,
      )}
    >
      <Building2 size={11} strokeWidth={1.75} className="text-[var(--color-brand)]" />
      {name}
      {code && <span className="text-[var(--color-text-muted)] font-mono">({code})</span>}
    </span>
  )
}
