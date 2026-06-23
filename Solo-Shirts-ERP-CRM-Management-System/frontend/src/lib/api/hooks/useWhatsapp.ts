'use client'

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiMutate } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'

export type WhatsappEvent =
  | 'order_confirmed'
  | 'order_ready_for_pickup'
  | 'payment_balance_reminder'
  | 'order_delivered'
  | 'delivery_rescheduled'

export const WHATSAPP_EVENT_LABELS: Record<WhatsappEvent, string> = {
  order_confirmed: 'Order confirmed',
  order_ready_for_pickup: 'Ready for pickup',
  payment_balance_reminder: 'Payment balance reminder',
  order_delivered: 'Order delivered',
  delivery_rescheduled: 'Delivery rescheduled',
}

export interface NotificationPreview {
  event_type: WhatsappEvent
  recipient_phone: string | null
  has_phone: boolean
  provider_configured: boolean
  message_body: string
}

export interface NotificationLog {
  id: number
  event_type: WhatsappEvent
  channel: string
  recipient_phone: string | null
  status: 'queued' | 'sent' | 'failed' | 'simulated'
  sent_by: string | null
  created_at: string | null
  sent_at: string | null
  preview: string
  error: string | null
}

export interface SendResult {
  notification_id: number
  event_type: WhatsappEvent
  recipient_phone: string | null
  status: 'queued' | 'sent' | 'failed' | 'simulated'
  message_body: string
}

export function orderNotificationsKey(orderId: number) {
  return ['order-notifications', orderId] as const
}

export function useNotificationPreview(orderId: number, eventType: WhatsappEvent, enabled: boolean) {
  return useQuery({
    queryKey: ['notification-preview', orderId, eventType],
    queryFn: () =>
      apiGet<NotificationPreview>(ENDPOINTS.notificationPreview(orderId), { event_type: eventType }),
    select: (res) => res.data,
    enabled: enabled && orderId > 0,
  })
}

export function useNotificationHistory(orderId: number) {
  return useQuery({
    queryKey: orderNotificationsKey(orderId),
    queryFn: () => apiGet<NotificationLog[]>(ENDPOINTS.orderNotifications(orderId)),
    select: (res) => res.data,
    enabled: orderId > 0,
  })
}

export function useSendWhatsapp(orderId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: { event_type: WhatsappEvent; message_body?: string; order_item_id?: number }) =>
      apiMutate<SendResult>('post', ENDPOINTS.sendWhatsapp(orderId), data).then((r) => r.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: orderNotificationsKey(orderId) })
    },
  })
}
