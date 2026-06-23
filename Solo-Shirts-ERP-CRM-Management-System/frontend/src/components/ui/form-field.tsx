'use client'

import { cloneElement, isValidElement, useId } from 'react'
import { cn } from '@/lib/utils'

interface FormFieldProps {
  id?: string
  label: string
  error?: string
  hint?: string
  required?: boolean
  children: React.ReactNode
  className?: string
}

export function FormField({ id, label, error, hint, required, children, className }: FormFieldProps) {
  const generatedId = useId()
  // Prefer an explicit id, then the child's own id, then a generated one — so the
  // label's htmlFor and the control's id always match without callers wiring it.
  const childId = isValidElement(children)
    ? (children as React.ReactElement<{ id?: string }>).props.id
    : undefined
  const fieldId = id ?? childId ?? generatedId
  const hintId = `${fieldId}-hint`
  const errorId = `${fieldId}-error`
  const describedBy =
    [hint && !error ? hintId : null, error ? errorId : null].filter(Boolean).join(' ') || undefined

  // Inject id + aria wiring onto the single control so the label points at it and
  // assistive tech ties the validation error/hint to the field.
  const control = isValidElement(children)
    ? cloneElement(children as React.ReactElement<Record<string, unknown>>, {
        id: fieldId,
        'aria-invalid': error ? true : undefined,
        'aria-describedby': describedBy,
      } as Record<string, unknown>)
    : children

  return (
    <div className={cn('space-y-1.5', className)}>
      <label
        htmlFor={fieldId}
        className="block text-sm font-medium text-[var(--color-text-primary)]"
      >
        {label}
        {required && (
          <span className="ml-0.5 text-[var(--color-danger)]" aria-hidden>*</span>
        )}
      </label>
      {control}
      {hint && !error && (
        <p id={hintId} className="text-xs text-[var(--color-text-muted)]">{hint}</p>
      )}
      {error && (
        <p id={errorId} className="text-xs text-[var(--color-danger)]" role="alert">{error}</p>
      )}
    </div>
  )
}

// Styled input primitive — use inside FormField
interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  error?: boolean
}

export function Input({ error, className, ...props }: InputProps) {
  return (
    <input
      className={cn(
        'w-full h-10 px-3 rounded-lg border text-sm',
        'text-[var(--color-text-primary)] bg-white',
        'focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:border-transparent',
        'placeholder:text-[var(--color-text-muted)] transition-shadow',
        error ? 'border-[var(--color-danger)]' : 'border-[var(--color-border-mid)]',
        'disabled:opacity-60 disabled:cursor-not-allowed',
        className,
      )}
      {...props}
    />
  )
}

// Styled textarea
interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  error?: boolean
}

export function Textarea({ error, className, ...props }: TextareaProps) {
  return (
    <textarea
      className={cn(
        'w-full px-3 py-2.5 rounded-lg border text-sm resize-none',
        'text-[var(--color-text-primary)] bg-white',
        'focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:border-transparent',
        'placeholder:text-[var(--color-text-muted)] transition-shadow',
        error ? 'border-[var(--color-danger)]' : 'border-[var(--color-border-mid)]',
        'disabled:opacity-60 disabled:cursor-not-allowed',
        className,
      )}
      {...props}
    />
  )
}

// Button with variants
type BtnVariant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'outline'

interface BtnProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: BtnVariant
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
}

const BTN_VARIANTS: Record<BtnVariant, string> = {
  primary:   'bg-[var(--color-brand)] hover:bg-[var(--color-brand-dark)] text-white',
  secondary: 'bg-[var(--color-brand-light)] hover:bg-[var(--color-brand-muted)] text-[var(--color-brand-dark)]',
  ghost:     'text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]',
  danger:    'bg-[var(--color-danger)] hover:bg-red-700 text-white',
  outline:   'border border-[var(--color-border-mid)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]',
}

const BTN_SIZES = {
  sm: 'h-8 px-3 text-xs rounded-lg',
  md: 'h-10 px-4 text-sm rounded-lg',
  lg: 'h-11 px-5 text-sm rounded-xl',
}

export function Button({ variant = 'primary', size = 'md', loading, children, className, disabled, ...props }: BtnProps) {
  return (
    <button
      className={cn(
        'inline-flex items-center justify-center gap-2 font-medium transition-colors',
        'focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:ring-offset-2',
        'disabled:opacity-60 disabled:cursor-not-allowed',
        BTN_VARIANTS[variant],
        BTN_SIZES[size],
        className,
      )}
      disabled={disabled || loading}
      {...props}
    >
      {loading && (
        <span className="w-4 h-4 rounded-full border-2 border-current border-t-transparent animate-spin shrink-0" aria-hidden />
      )}
      {children}
    </button>
  )
}
