'use client'

import { Check } from 'lucide-react'
import { cn } from '@/lib/utils'
import { STEP_LABELS, WIZARD_STEPS, type WizardStep } from './types'

interface WizardStepperProps {
  activeIndex: number
  maxReachedIndex: number
  /** Steps below this index are locked (e.g. after the order is created). */
  minIndex?: number
  onStepClick: (step: WizardStep) => void
}

/**
 * Horizontal step rail. Visited steps are clickable to jump back; future steps
 * are locked until reached. Steps below minIndex are locked entirely.
 */
export function WizardStepper({ activeIndex, maxReachedIndex, minIndex = 0, onStepClick }: WizardStepperProps) {
  return (
    <ol className="flex items-center gap-1 overflow-x-auto ss-no-scrollbar">
      {WIZARD_STEPS.map((step, i) => {
        const isActive = i === activeIndex
        const isComplete = i < activeIndex
        const reachable = i <= maxReachedIndex && i >= minIndex
        return (
          <li key={step} className="flex items-center shrink-0">
            <button
              type="button"
              disabled={!reachable}
              onClick={() => reachable && onStepClick(step)}
              className={cn(
                'flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                isActive && 'bg-[var(--color-brand)] text-white',
                !isActive && isComplete && 'text-[var(--color-brand-dark)] hover:bg-[var(--color-brand-light)]',
                !isActive && !isComplete && 'text-[var(--color-text-muted)]',
                !reachable && 'cursor-not-allowed opacity-60',
              )}
            >
              <span
                className={cn(
                  'flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold',
                  isActive && 'bg-white/20 text-white',
                  !isActive && isComplete && 'bg-[var(--color-brand)] text-white',
                  !isActive && !isComplete && 'bg-[var(--color-surface-alt)] text-[var(--color-text-muted)]',
                )}
              >
                {isComplete ? <Check size={12} strokeWidth={2.5} /> : i + 1}
              </span>
              {STEP_LABELS[step]}
            </button>
            {i < WIZARD_STEPS.length - 1 && (
              <span className="mx-1 h-px w-4 bg-[var(--color-border)]" aria-hidden />
            )}
          </li>
        )
      })}
    </ol>
  )
}
