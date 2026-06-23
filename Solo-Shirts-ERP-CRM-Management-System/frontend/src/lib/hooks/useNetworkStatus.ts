'use client'

import { useState, useEffect } from 'react'

interface NetworkStatus {
  isOnline: boolean
  isSlowNetwork: boolean
}

export function useNetworkStatus(): NetworkStatus {
  const [isOnline, setIsOnline] = useState(
    typeof navigator !== 'undefined' ? navigator.onLine : true,
  )
  const [isSlowNetwork, setIsSlowNetwork] = useState(false)

  useEffect(() => {
    function handleOnline() {
      setIsOnline(true)
    }
    function handleOffline() {
      setIsOnline(false)
    }
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)

    const nav = navigator as Navigator & { connection?: { effectiveType?: string } }
    if (nav.connection) {
      setIsSlowNetwork(['slow-2g', '2g'].includes(nav.connection.effectiveType ?? ''))
    }

    return () => {
      window.removeEventListener('online', handleOnline)
      window.removeEventListener('offline', handleOffline)
    }
  }, [])

  return { isOnline, isSlowNetwork }
}
