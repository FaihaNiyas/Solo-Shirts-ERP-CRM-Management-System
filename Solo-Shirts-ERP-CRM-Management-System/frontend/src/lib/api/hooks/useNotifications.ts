'use client'

import { useQuery } from '@tanstack/react-query'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { queryKeys } from '@/lib/query/keys'
import type { Notification } from '@/lib/api/schemas/notifications'

interface NotificationList {
  items: Notification[]
  unread_count: number
}

export function useNotifications() {
  return useQuery({
    queryKey: queryKeys.notifications(),
    queryFn: () => apiGet<NotificationList>(ENDPOINTS.notifications),
    select: (res) => res.data,
    refetchInterval: 60_000,   // 60s — halves background polling load across all pages
    staleTime: 60_000,
  })
}

export function useUnreadCount() {
  const { data } = useNotifications()
  return data?.unread_count ?? 0
}
