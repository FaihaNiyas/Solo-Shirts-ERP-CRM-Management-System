import { QueryClient } from '@tanstack/react-query'

export function makeQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 3 * 60 * 1000,       // 3 min — reduces refetches on navigation
        gcTime: 10 * 60 * 1000,         // 10 min — keep cache when switching pages
        refetchOnWindowFocus: false,
        refetchOnMount: false,          // serve cached data when navigating back
        retry: (failureCount, error) => {
          const status = (error as { response?: { status?: number } })?.response?.status
          if (status && status >= 400 && status < 500) return false
          return failureCount < 2
        },
      },
      mutations: {
        retry: false,
      },
    },
  })
}

// Browser singleton
let browserClient: QueryClient | undefined

export function getQueryClient(): QueryClient {
  if (typeof window === 'undefined') {
    return makeQueryClient()
  }
  if (!browserClient) {
    browserClient = makeQueryClient()
  }
  return browserClient
}
