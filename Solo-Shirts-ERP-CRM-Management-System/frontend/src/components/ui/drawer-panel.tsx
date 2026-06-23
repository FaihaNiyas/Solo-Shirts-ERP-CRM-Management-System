'use client'

import * as Dialog from '@radix-ui/react-dialog'
import { motion, AnimatePresence } from 'framer-motion'
import { X } from 'lucide-react'
import { cn } from '@/lib/utils'

interface DrawerPanelProps {
  open: boolean
  onClose: () => void
  title?: React.ReactNode
  description?: string
  children: React.ReactNode
  width?: 'sm' | 'md' | 'lg' | 'xl'
  size?: 'sm' | 'md' | 'lg' | 'xl'
  footer?: React.ReactNode
}

const widths = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-2xl',
}

export function DrawerPanel({
  open,
  onClose,
  title,
  description,
  children,
  width,
  size,
  footer,
}: DrawerPanelProps) {
  const resolvedWidth: 'sm' | 'md' | 'lg' | 'xl' = width ?? size ?? 'md'
  return (
    <Dialog.Root open={open} onOpenChange={(v) => !v && onClose()}>
      <AnimatePresence>
        {open && (
          <Dialog.Portal forceMount>
            {/* Overlay */}
            <Dialog.Overlay asChild>
              <motion.div
                className="fixed inset-0 z-40 bg-black/25"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.15 }}
              />
            </Dialog.Overlay>

            {/* Panel */}
            <Dialog.Content asChild onInteractOutside={onClose}>
              <motion.aside
                className={cn(
                  'fixed inset-y-0 right-0 z-50 flex flex-col bg-white shadow-[var(--shadow-xl)]',
                  'w-full',
                  widths[resolvedWidth],
                )}
                initial={{ x: '100%' }}
                animate={{ x: 0 }}
                exit={{ x: '100%' }}
                transition={{ type: 'tween', duration: 0.2, ease: 'easeOut' }}
                aria-describedby={description ? 'drawer-desc' : undefined}
              >
                {/* Header */}
                <div className="flex items-start justify-between px-5 py-4 border-b border-[var(--color-border)] shrink-0">
                  <div>
                    {/* Radix requires a Dialog.Title for an accessible name —
                        render an sr-only fallback when no visible title is given. */}
                    {title ? (
                      <Dialog.Title className="text-base font-semibold text-[var(--color-text-primary)]">
                        {title}
                      </Dialog.Title>
                    ) : (
                      <Dialog.Title className="sr-only">Dialog</Dialog.Title>
                    )}
                    {description && (
                      <Dialog.Description id="drawer-desc" className="mt-0.5 text-sm text-[var(--color-text-muted)]">
                        {description}
                      </Dialog.Description>
                    )}
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

                {/* Scrollable body */}
                <div className="flex-1 overflow-y-auto px-5 py-4">{children}</div>

                {/* Footer */}
                {footer && (
                  <div className="px-5 py-4 border-t border-[var(--color-border)] shrink-0 bg-[var(--color-surface-alt)]">
                    {footer}
                  </div>
                )}
              </motion.aside>
            </Dialog.Content>
          </Dialog.Portal>
        )}
      </AnimatePresence>
    </Dialog.Root>
  )
}
