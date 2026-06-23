'use client'

import * as Collapsible from '@radix-ui/react-collapsible'
import { ChevronDown } from 'lucide-react'
import { motion, AnimatePresence } from 'framer-motion'
import { useState } from 'react'
import { cn } from '@/lib/utils'

interface CollapsibleSectionProps {
  title: string
  description?: string
  defaultOpen?: boolean
  children: React.ReactNode
  badge?: React.ReactNode
  className?: string
}

export function CollapsibleSection({
  title,
  description,
  defaultOpen = true,
  children,
  badge,
  className,
}: CollapsibleSectionProps) {
  const [open, setOpen] = useState(defaultOpen)

  return (
    <Collapsible.Root open={open} onOpenChange={setOpen} className={className}>
      <Collapsible.Trigger asChild>
        <button
          className={cn(
            'flex items-center justify-between w-full px-4 py-3',
            'border border-[var(--color-border)] rounded-xl bg-[var(--color-surface-alt)]',
            'text-left transition-colors hover:bg-[var(--color-border)]',
            open && 'rounded-b-none border-b-0',
          )}
        >
          <div className="flex items-center gap-2">
            <span className="text-sm font-semibold text-[var(--color-text-primary)]">{title}</span>
            {description && (
              <span className="text-xs text-[var(--color-text-muted)]">{description}</span>
            )}
            {badge && badge}
          </div>
          <ChevronDown
            size={16}
            strokeWidth={1.75}
            className={cn(
              'text-[var(--color-text-muted)] transition-transform duration-200',
              open && 'rotate-180',
            )}
          />
        </button>
      </Collapsible.Trigger>

      <AnimatePresence initial={false}>
        {open && (
          <Collapsible.Content asChild forceMount>
            <motion.div
              initial={{ height: 0, opacity: 0 }}
              animate={{ height: 'auto', opacity: 1 }}
              exit={{ height: 0, opacity: 0 }}
              transition={{ duration: 0.2, ease: 'easeInOut' }}
              className="overflow-hidden"
            >
              <div className="border border-[var(--color-border)] border-t-0 rounded-b-xl p-4">
                {children}
              </div>
            </motion.div>
          </Collapsible.Content>
        )}
      </AnimatePresence>
    </Collapsible.Root>
  )
}
