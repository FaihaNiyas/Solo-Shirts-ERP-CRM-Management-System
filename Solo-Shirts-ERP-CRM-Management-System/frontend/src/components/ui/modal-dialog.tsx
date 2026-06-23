'use client'

import * as Dialog from '@radix-ui/react-dialog'
import { motion, AnimatePresence } from 'framer-motion'
import { X } from 'lucide-react'
import { cn } from '@/lib/utils'

interface ModalDialogProps {
  open: boolean
  onClose: () => void
  title?: string
  description?: string
  children: React.ReactNode
  size?: 'sm' | 'md' | 'lg' | 'xl'
  footer?: React.ReactNode
}

const sizes = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-2xl',
}

export function ModalDialog({ open, onClose, title, description, children, size = 'md', footer }: ModalDialogProps) {
  return (
    <Dialog.Root open={open} onOpenChange={(v) => !v && onClose()}>
      <AnimatePresence>
        {open && (
          <Dialog.Portal forceMount>
            <Dialog.Overlay asChild>
              <motion.div
                className="fixed inset-0 z-50 bg-black/30 backdrop-blur-[2px] flex items-center justify-center p-4"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.2 }}
              >
                <Dialog.Content asChild onInteractOutside={onClose}>
                  <motion.div
                    className={cn(
                      'bg-white rounded-2xl shadow-[var(--shadow-xl)] w-full overflow-hidden',
                      sizes[size],
                    )}
                    initial={{ scale: 0.95, opacity: 0, y: 12 }}
                    animate={{ scale: 1, opacity: 1, y: 0 }}
                    exit={{ scale: 0.97, opacity: 0, y: 8 }}
                    transition={{ duration: 0.24, ease: [0.16, 1, 0.3, 1] }}
                    onClick={(e) => e.stopPropagation()}
                  >
                    {/* Header — Radix requires a Title + Description; render
                        them sr-only when not provided (e.g. ConfirmDialog supplies
                        its own visible heading) to satisfy a11y without warnings. */}
                    <div className="flex items-start justify-between px-5 py-4 border-b border-[var(--color-border)]">
                      <div>
                        <Dialog.Title className={cn('text-base font-semibold text-[var(--color-text-primary)]', !title && 'sr-only')}>
                          {title ?? 'Dialog'}
                        </Dialog.Title>
                        <Dialog.Description className={cn('mt-0.5 text-sm text-[var(--color-text-muted)]', !description && 'sr-only')}>
                          {description ?? ''}
                        </Dialog.Description>
                      </div>
                      <Dialog.Close asChild>
                        <button
                          className="ml-4 flex items-center justify-center w-7 h-7 rounded-lg text-[var(--color-text-muted)] hover:bg-[var(--color-surface-alt)] transition-colors"
                          aria-label="Close"
                        >
                          <X size={16} strokeWidth={1.75} />
                        </button>
                      </Dialog.Close>
                    </div>

                    {/* Body */}
                    <div className="px-5 py-4">{children}</div>

                    {/* Footer */}
                    {footer && (
                      <div className="px-5 py-4 border-t border-[var(--color-border)] bg-[var(--color-surface-alt)] flex items-center justify-end gap-2">
                        {footer}
                      </div>
                    )}
                  </motion.div>
                </Dialog.Content>
              </motion.div>
            </Dialog.Overlay>
          </Dialog.Portal>
        )}
      </AnimatePresence>
    </Dialog.Root>
  )
}
