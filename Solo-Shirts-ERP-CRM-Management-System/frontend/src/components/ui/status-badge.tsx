import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'
import { productionStateLabel, productionStateVariant } from '@/lib/orders/productionState'

const badgeVariants = cva(
  'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
  {
    variants: {
      variant: {
        // Generic status
        pending:  'bg-amber-100 text-amber-800',
        active:   'bg-green-100 text-green-700',
        inactive: 'bg-red-100 text-red-700',
        complete: 'bg-green-100 text-green-800',
        error:    'bg-red-100 text-red-700',
        warning:  'bg-orange-100 text-orange-700',
        info:     'bg-blue-50 text-blue-600',
        neutral:  'bg-gray-100 text-gray-600',
        draft:    'bg-gray-100 text-gray-500',

        // Order lifecycle (Phase 2.5)
        intake_preparation: 'bg-amber-50 text-amber-700 border border-amber-200',
        order_received:     'bg-blue-50 text-blue-700 border border-blue-200',
        in_production:      'bg-blue-50 text-blue-600',

        // Production stages — amber accent, blue only for in-progress stages, no purple/teal
        Draft:              'bg-gray-100 text-gray-500',
        FabricAllocated:    'bg-amber-50 text-amber-700 border border-amber-200',
        Cutting:            'bg-amber-100 text-amber-800',
        Tailoring:          'bg-blue-50 text-blue-600',
        KajaButton:         'bg-blue-100 text-blue-700',
        Finishing:          'bg-blue-100 text-blue-700',
        QC:                 'bg-gray-100 text-gray-700 border border-amber-200',
        Packing:            'bg-gray-100 text-gray-600',
        ReadyForDelivery:   'bg-green-100 text-green-700',
        Delivered:          'bg-green-50 text-green-600',
        Rework:             'bg-amber-100 text-amber-800 border border-amber-300',
        Cancelled:          'bg-gray-100 text-gray-500 line-through',

        // Approval
        approved:           'bg-green-100 text-green-800',
        pending_approval:   'bg-amber-100 text-amber-700',
        rejected:           'bg-red-100 text-red-700',
      },
    },
    defaultVariants: {
      variant: 'neutral',
    },
  },
)

const dotColors: Record<string, string> = {
  pending:   'bg-amber-500',
  active:    'bg-green-500',
  inactive:  'bg-red-500',
  complete:  'bg-green-500',
  error:     'bg-red-500',
  warning:   'bg-orange-500',
  Rework:    'bg-amber-500',
  QC:        'bg-amber-400',
}

interface StatusBadgeProps extends VariantProps<typeof badgeVariants> {
  status?: string
  label?: string
  showDot?: boolean
  className?: string
}

export function StatusBadge({ variant, status, label, showDot = false, className }: StatusBadgeProps) {
  const resolved = (status ?? variant) as typeof variant
  const dotColor = resolved ? dotColors[resolved] : undefined

  return (
    <span className={cn(badgeVariants({ variant: resolved }), className)}>
      {showDot && dotColor && (
        <span className={cn('w-1.5 h-1.5 rounded-full shrink-0', dotColor)} aria-hidden />
      )}
      {label ?? (resolved ?? 'Unknown')}
    </span>
  )
}

/**
 * Production-stage badge. `state` may be the API's snake_case state
 * (e.g. "ready_for_delivery") or a legacy PascalCase variant key — both resolve
 * to the correct colour + label via the single canonical mapper. An explicit
 * `label` (e.g. the backend's production_state_label) overrides the derived one.
 */
export function ProductionBadge({ state, label, className }: { state: string; label?: string; className?: string }) {
  const variant = productionStateVariant(state) as Parameters<typeof StatusBadge>[0]['variant']
  return (
    <StatusBadge
      variant={variant}
      label={label ?? productionStateLabel(state)}
      showDot={state === 'qc' || state === 'QC'}
      className={className}
    />
  )
}
