'use client'

import { useState } from 'react'
import { format } from 'date-fns'
import { MessageCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { usePermission } from '@/lib/auth/permissions'
import {
  WHATSAPP_EVENT_LABELS,
  useNotificationHistory,
  type NotificationLog,
  type WhatsappEvent,
} from '@/lib/api/hooks/useWhatsapp'
import { WhatsAppTriggerModal } from './WhatsAppTriggerModal'

const STATUS_CLS: Record<NotificationLog['status'], string> = {
  sent: 'bg-green-100 text-green-700',
  queued: 'bg-blue-50 text-blue-700',
  simulated: 'bg-amber-50 text-amber-800',
  failed: 'bg-red-100 text-red-700',
}

export function OrderNotificationsSection({
  orderId,
  events,
}: {
  orderId: number
  events: WhatsappEvent[]
}) {
  const { can } = usePermission()
  const { data, isLoading } = useNotificationHistory(orderId)
  const [open, setOpen] = useState(false)

  const canSend = can('orders.notifications.send')

  return (
    <section className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-base font-semibold text-[var(--color-text-primary)]">Customer notifications (WhatsApp)</h2>
        {canSend && events.length > 0 && (
          <button
            type="button"
            onClick={() => setOpen(true)}
            className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-3 h-9 text-sm font-medium text-white hover:bg-[var(--color-brand-dark)] transition-colors"
          >
            <MessageCircle size={15} strokeWidth={1.75} /> Send WhatsApp
          </button>
        )}
      </div>

      {isLoading ? (
        <p className="py-4 text-center text-sm text-[var(--color-text-muted)]">Loading…</p>
      ) : !data || data.length === 0 ? (
        <p className="py-4 text-center text-sm text-[var(--color-text-muted)]">No messages sent yet.</p>
      ) : (
        <div className="space-y-2">
          {data.map((n) => (
            <div key={n.id} className="rounded-lg border border-[var(--color-border)] px-3 py-2">
              <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                <span className="font-medium text-[var(--color-text-primary)]">
                  {WHATSAPP_EVENT_LABELS[n.event_type] ?? n.event_type}
                </span>
                <span className={cn('rounded-full px-2 py-0.5 text-[11px] font-medium', STATUS_CLS[n.status])}>
                  {n.status}
                </span>
                <span className="text-xs text-[var(--color-text-muted)]">{n.recipient_phone ?? '—'}</span>
                <span className="ml-auto text-xs text-[var(--color-text-muted)]">
                  {n.created_at ? format(new Date(n.created_at), 'dd MMM, HH:mm') : ''}
                  {n.sent_by ? ` · ${n.sent_by}` : ''}
                </span>
              </div>
              <p className="mt-1 text-xs text-[var(--color-text-muted)] truncate">{n.preview}</p>
              {n.error && <p className="mt-0.5 text-xs text-[var(--color-danger)]">{n.error}</p>}
            </div>
          ))}
        </div>
      )}

      <WhatsAppTriggerModal orderId={orderId} open={open} onClose={() => setOpen(false)} events={events} />
    </section>
  )
}
