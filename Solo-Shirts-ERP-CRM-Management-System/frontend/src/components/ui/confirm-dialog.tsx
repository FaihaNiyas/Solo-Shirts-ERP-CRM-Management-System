'use client'

import { useState } from 'react'
import { AlertTriangle, Trash2, Info } from 'lucide-react'
import { cn } from '@/lib/utils'
import { ModalDialog } from './modal-dialog'

type ConfirmVariant = 'destructive' | 'danger' | 'warning' | 'info'

interface ConfirmDialogProps {
  open: boolean
  onClose: () => void
  onConfirm: (reason?: string) => void | Promise<void>
  title: string
  description?: string
  variant?: ConfirmVariant
  confirmLabel?: string
  cancelLabel?: string
  requireReason?: boolean
  reasonLabel?: string
  loading?: boolean
}

const ICONS: Record<ConfirmVariant, typeof Trash2> = {
  destructive: Trash2,
  danger: Trash2,
  warning: AlertTriangle,
  info: Info,
}

const ICON_STYLES: Record<ConfirmVariant, string> = {
  destructive: 'bg-red-50 text-[var(--color-danger)]',
  danger: 'bg-red-50 text-[var(--color-danger)]',
  warning: 'bg-amber-50 text-amber-600',
  info: 'bg-blue-50 text-blue-600',
}

const CONFIRM_STYLES: Record<ConfirmVariant, string> = {
  destructive: 'bg-[var(--color-danger)] hover:bg-red-700 text-white',
  danger: 'bg-[var(--color-danger)] hover:bg-red-700 text-white',
  warning: 'bg-amber-600 hover:bg-amber-700 text-white',
  info: 'bg-[var(--color-brand)] hover:bg-[var(--color-brand-dark)] text-white',
}

export function ConfirmDialog({
  open,
  onClose,
  onConfirm,
  title,
  description,
  variant = 'warning',
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  requireReason = false,
  reasonLabel = 'Reason',
  loading = false,
}: ConfirmDialogProps) {
  const [reason, setReason] = useState('')
  const Icon = ICONS[variant]

  async function handleConfirm() {
    await onConfirm(requireReason ? reason : undefined)
  }

  return (
    <ModalDialog open={open} onClose={onClose} size="sm">
      <div className="flex gap-4">
        <div
          className={cn(
            'flex items-center justify-center w-10 h-10 rounded-xl shrink-0',
            ICON_STYLES[variant],
          )}
        >
          <Icon size={20} strokeWidth={1.75} />
        </div>
        <div className="flex-1 min-w-0">
          <h3 className="text-sm font-semibold text-[var(--color-text-primary)]">{title}</h3>
          {description && (
            <p className="mt-1 text-sm text-[var(--color-text-secondary)]">{description}</p>
          )}
          {requireReason && (
            <div className="mt-3">
              <label className="block text-xs font-medium text-[var(--color-text-primary)] mb-1.5">
                {reasonLabel} <span className="text-[var(--color-danger)]">*</span>
              </label>
              <textarea
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                rows={2}
                placeholder={`Enter ${reasonLabel.toLowerCase()}…`}
                className="w-full rounded-lg border border-[var(--color-border-mid)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] resize-none"
              />
            </div>
          )}
        </div>
      </div>

      <div className="flex items-center justify-end gap-2 mt-5">
        <button
          onClick={onClose}
          className="px-4 py-2 rounded-lg text-sm font-medium text-[var(--color-text-secondary)] border border-[var(--color-border)] hover:bg-[var(--color-surface-alt)] transition-colors"
        >
          {cancelLabel}
        </button>
        <button
          onClick={handleConfirm}
          disabled={loading || (requireReason && !reason.trim())}
          className={cn(
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            'disabled:opacity-60 disabled:cursor-not-allowed',
            CONFIRM_STYLES[variant],
          )}
        >
          {loading ? 'Processing…' : confirmLabel}
        </button>
      </div>
    </ModalDialog>
  )
}
