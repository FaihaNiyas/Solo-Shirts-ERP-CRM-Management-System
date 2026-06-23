'use client'

import { Check } from 'lucide-react'
import { cn } from '@/lib/utils'

interface Step {
  id: string | number
  label: string
  description?: string
}

interface StepperNavProps {
  steps: Step[]
  currentStep: number
  onStepClick?: (index: number) => void
  className?: string
}

export function StepperNav({ steps, currentStep, onStepClick, className }: StepperNavProps) {
  return (
    <nav
      aria-label="Progress"
      className={cn('flex items-center gap-0', className)}
    >
      {steps.map((step, i) => {
        const done = i < currentStep
        const active = i === currentStep
        const clickable = done && Boolean(onStepClick)

        return (
          <div key={step.id} className="flex items-center flex-1 last:flex-none">
            <button
              onClick={() => clickable && onStepClick?.(i)}
              disabled={!clickable && !active}
              className={cn(
                'flex items-center gap-2.5 shrink-0',
                clickable ? 'cursor-pointer' : 'cursor-default',
              )}
              aria-current={active ? 'step' : undefined}
            >
              {/* Circle */}
              <span
                className={cn(
                  'flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold shrink-0',
                  'transition-colors',
                  done && 'bg-[var(--color-brand)] text-white',
                  active && 'bg-[var(--color-brand)] text-white ring-4 ring-[var(--color-brand-light)]',
                  !done && !active && 'bg-[var(--color-border)] text-[var(--color-text-muted)]',
                )}
              >
                {done ? <Check size={14} strokeWidth={2.5} /> : i + 1}
              </span>

              {/* Label */}
              <div className="hidden sm:block text-left">
                <p
                  className={cn(
                    'text-xs font-medium',
                    active ? 'text-[var(--color-brand)]' : done ? 'text-[var(--color-text-primary)]' : 'text-[var(--color-text-muted)]',
                  )}
                >
                  {step.label}
                </p>
                {step.description && (
                  <p className="text-[11px] text-[var(--color-text-muted)]">{step.description}</p>
                )}
              </div>
            </button>

            {/* Connector */}
            {i < steps.length - 1 && (
              <div className="flex-1 mx-2 h-px bg-[var(--color-border)]">
                <div
                  className="h-full bg-[var(--color-brand)] transition-all duration-300"
                  style={{ width: done ? '100%' : '0%' }}
                />
              </div>
            )}
          </div>
        )
      })}
    </nav>
  )
}
