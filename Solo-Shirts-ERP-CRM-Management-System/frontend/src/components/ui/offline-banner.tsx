'use client'

import { useEffect, useState } from 'react'
import { WifiOff, X } from 'lucide-react'
import { motion, AnimatePresence } from 'framer-motion'
import { useNetworkStatus } from '@/lib/hooks/useNetworkStatus'

export function OfflineBanner() {
  const { isOnline } = useNetworkStatus()
  const [dismissed, setDismissed] = useState(false)

  useEffect(() => {
    if (!isOnline) setDismissed(false)
  }, [isOnline])

  return (
    <AnimatePresence>
      {!isOnline && !dismissed && (
        <motion.div
          key="offline"
          initial={{ y: -48, opacity: 0 }}
          animate={{ y: 0, opacity: 1 }}
          exit={{ y: -48, opacity: 0 }}
          transition={{ duration: 0.2, ease: 'easeOut' }}
          className="fixed top-0 inset-x-0 z-[200] flex items-center justify-between px-4 py-2.5 bg-red-600 text-white text-sm font-medium"
        >
          <div className="flex items-center gap-2">
            <WifiOff size={15} strokeWidth={1.75} />
            No internet connection. Changes will be saved when you reconnect.
          </div>
          <button
            onClick={() => setDismissed(true)}
            className="ml-4 opacity-80 hover:opacity-100 transition-opacity"
            aria-label="Dismiss"
          >
            <X size={15} strokeWidth={1.75} />
          </button>
        </motion.div>
      )}
    </AnimatePresence>
  )
}
