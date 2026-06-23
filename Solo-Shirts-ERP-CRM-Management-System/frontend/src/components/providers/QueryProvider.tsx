'use client'

import { useRef } from 'react'
import { QueryClientProvider, QueryClient } from '@tanstack/react-query'
import { makeQueryClient } from '@/lib/query/client'

export function QueryProvider({ children }: { children: React.ReactNode }) {
  // useRef ensures exactly one client per React tree, stable across re-renders
  const clientRef = useRef<QueryClient | null>(null)
  if (!clientRef.current) {
    clientRef.current = makeQueryClient()
  }
  return (
    <QueryClientProvider client={clientRef.current}>
      {children}
    </QueryClientProvider>
  )
}
