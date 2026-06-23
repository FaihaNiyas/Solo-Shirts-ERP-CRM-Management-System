'use client'

import { TrendingUp, TrendingDown, Minus } from 'lucide-react'
import { cn } from '@/lib/utils'

type Variant = 'default' | 'positive' | 'warning' | 'danger'
type TrendDir = 'up' | 'down' | 'flat'

interface MetricCardProps {
  label: string
  value: string | number
  icon?: React.ElementType
  /** Trend text e.g. "12.4%" or "3 items" */
  trend?: string
  trendDir?: TrendDir
  variant?: Variant
  /** Optional mini-series for the sparkline. When omitted, no sparkline is drawn. */
  data?: number[]
  className?: string
}

const VARIANT_ICON: Record<Variant, string> = {
  default:  'text-[var(--color-brand)] bg-[var(--color-brand-light)]',
  positive: 'text-[var(--color-success)] bg-[var(--bg-success)]',
  warning:  'text-[var(--color-warning)] bg-[var(--bg-warning)]',
  danger:   'text-[var(--color-danger)] bg-[var(--bg-danger)]',
}

const VARIANT_BORDER: Record<Variant, string> = {
  default:  '',
  positive: '',
  warning:  'border-l-[3px] border-l-[var(--color-warning)]',
  danger:   'border-l-[3px] border-l-[var(--color-danger)]',
}

const TREND_STYLE: Record<TrendDir, { color: string; Icon: React.ElementType }> = {
  up:   { color: 'text-[var(--color-success)]', Icon: TrendingUp },
  down: { color: 'text-[var(--color-danger)]',  Icon: TrendingDown },
  flat: { color: 'text-[var(--color-text-muted)]', Icon: Minus },
}

const SPARK_COLOR: Record<TrendDir, string> = {
  up:   'var(--color-success)',
  down: 'var(--color-danger)',
  flat: 'var(--color-brand-muted)',
}

function Sparkline({ data, color, w = 200, h = 32 }: { data: number[]; color: string; w?: number; h?: number }) {
  if (data.length < 2) return null
  const min = Math.min(...data)
  const max = Math.max(...data)
  const span = max - min || 1
  const pts = data.map((v, i) => [
    (i / (data.length - 1)) * w,
    h - ((v - min) / span) * (h - 5) - 3,
  ])
  const line = pts.map((p, i) => `${i ? 'L' : 'M'}${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ')
  const area = `${line} L${w} ${h} L0 ${h} Z`
  const last = pts[pts.length - 1]
  return (
    <svg
      viewBox={`0 0 ${w} ${h}`}
      preserveAspectRatio="none"
      className="w-full"
      style={{ height: h, display: 'block', overflow: 'visible' }}
      aria-hidden
    >
      <path d={area} fill={color} opacity={0.1} />
      <path d={line} fill="none" stroke={color} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" vectorEffect="non-scaling-stroke" />
      <circle cx={last[0]} cy={last[1]} r={2.5} fill={color} />
    </svg>
  )
}

export function MetricCard({
  label,
  value,
  icon: Icon,
  trend,
  trendDir = 'flat',
  variant = 'default',
  data,
  className,
}: MetricCardProps) {
  const trendStyle = TREND_STYLE[trendDir]
  return (
    <div className={cn('ss-kpi ss-card-hover', VARIANT_BORDER[variant], className)}>
      <div className="flex items-center justify-between gap-2">
        <span className="ss-kpi-label truncate">{label}</span>
        {Icon && (
          <span className={cn('flex items-center justify-center w-9 h-9 rounded-[10px] shrink-0', VARIANT_ICON[variant])}>
            <Icon size={17} strokeWidth={1.75} />
          </span>
        )}
      </div>

      <p className="ss-kpi-value">{value}</p>

      {trend && (
        <p className={cn('mt-2 inline-flex items-center gap-1 text-xs font-medium', trendStyle.color)}>
          <trendStyle.Icon size={13} strokeWidth={2} />
          {trend}
        </p>
      )}

      {data && data.length >= 2 && (
        <div className="mt-3 -mb-1">
          <Sparkline data={data} color={SPARK_COLOR[trendDir]} />
        </div>
      )}
    </div>
  )
}
