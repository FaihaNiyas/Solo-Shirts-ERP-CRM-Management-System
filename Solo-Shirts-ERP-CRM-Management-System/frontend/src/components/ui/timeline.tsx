import { cn } from '@/lib/utils'
import { format } from 'date-fns'

interface TimelineEvent {
  id: string | number
  title: string
  description?: string
  actor?: string
  timestamp: string
  status?: 'success' | 'warning' | 'error' | 'info' | 'neutral'
}

interface TimelineProps {
  events: TimelineEvent[]
  className?: string
}

const STATUS_DOT: Record<string, string> = {
  success: 'bg-green-500',
  warning: 'bg-amber-500',
  error: 'bg-red-500',
  info: 'bg-blue-500',
  neutral: 'bg-gray-400',
}

export function Timeline({ events, className }: TimelineProps) {
  return (
    <ol className={cn('space-y-0', className)}>
      {events.map((event, i) => (
        <li key={event.id} className="relative flex gap-3 pb-5 last:pb-0">
          {/* Connector line */}
          {i < events.length - 1 && (
            <span
              className="absolute left-[7px] top-3.5 bottom-0 w-px bg-[var(--color-border)]"
              aria-hidden
            />
          )}

          {/* Dot */}
          <span
            className={cn(
              'relative z-10 mt-0.5 flex-shrink-0 w-3.5 h-3.5 rounded-full ring-2 ring-white',
              STATUS_DOT[event.status ?? 'neutral'],
            )}
            aria-hidden
          />

          {/* Content */}
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-[var(--color-text-primary)] leading-snug">
              {event.title}
            </p>
            {event.description && (
              <p className="mt-0.5 text-xs text-[var(--color-text-secondary)]">
                {event.description}
              </p>
            )}
            <div className="flex items-center gap-2 mt-1">
              {event.actor && (
                <span className="text-xs text-[var(--color-text-muted)]">{event.actor}</span>
              )}
              {event.actor && (
                <span className="text-xs text-[var(--color-text-muted)]">·</span>
              )}
              <time className="text-xs text-[var(--color-text-muted)]">
                {format(new Date(event.timestamp), 'dd MMM yyyy, h:mm a')}
              </time>
            </div>
          </div>
        </li>
      ))}
    </ol>
  )
}
