import { z } from 'zod'

export const NotificationSchema = z.object({
  id: z.number(),
  type: z.string(),
  title: z.string(),
  body: z.string(),
  data: z.record(z.unknown()).nullable().optional(),
  channel: z.enum(['app', 'whatsapp', 'sms', 'email']).optional(),
  read_at: z.string().nullable(),
  created_at: z.string(),
})

export const NotificationListSchema = z.object({
  data: z.array(NotificationSchema),
  unread_count: z.number(),
})

export type Notification = z.infer<typeof NotificationSchema>
