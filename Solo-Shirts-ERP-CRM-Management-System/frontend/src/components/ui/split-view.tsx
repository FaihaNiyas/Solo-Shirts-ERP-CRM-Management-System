import { cn } from '@/lib/utils'

interface SplitViewProps {
  left: React.ReactNode
  right: React.ReactNode
  leftWidth?: string
  rightWidth?: string
  className?: string
  gap?: 'sm' | 'md' | 'lg'
}

const GAP = { sm: 'gap-3', md: 'gap-4', lg: 'gap-6' }

export function SplitView({
  left,
  right,
  leftWidth = 'w-72',
  rightWidth,
  className,
  gap = 'md',
}: SplitViewProps) {
  return (
    <div className={cn('flex h-full', GAP[gap], className)}>
      <div className={cn('flex-shrink-0', leftWidth, 'overflow-y-auto')}>{left}</div>
      <div className={cn('flex-1 min-w-0 overflow-y-auto', rightWidth)}>{right}</div>
    </div>
  )
}
