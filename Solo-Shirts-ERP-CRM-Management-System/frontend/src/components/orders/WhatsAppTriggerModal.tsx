'use client'

import { useEffect, useState } from 'react'
import { MessageCircle, AlertTriangle, CheckCircle2, Loader2, Info } from 'lucide-react'
import { cn } from '@/lib/utils'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import {
  WHATSAPP_EVENT_LABELS,
  useNotificationPreview,
  useSendWhatsapp,
  type SendResult,
  type WhatsappEvent,
} from '@/lib/api/hooks/useWhatsapp'

const STATUS_COPY: Record<SendResult['status'], { label: string; cls: string }> = {
  sent: { label: 'Message sent', cls: 'bg-green-100 text-green-700' },
  queued: { label: 'Message queued', cls: 'bg-blue-50 text-blue-700' },
  simulated: { label: 'WhatsApp simulated — provider not configured', cls: 'bg-amber-50 text-amber-800' },
  failed: { label: 'Send failed', cls: 'bg-red-100 text-red-700' },
}

export function WhatsAppTriggerModal({
  orderId,
  open,
  onClose,
  events,
}: {
  orderId: number
  open: boolean
  onClose: () => void
  events: WhatsappEvent[]
}) {
  const [event, setEvent] = useState<WhatsappEvent>(events[0] ?? 'order_confirmed')
  const [body, setBody] = useState('')
  const [result, setResult] = useState<SendResult | null>(null)

  const preview = useNotificationPreview(orderId, event, open)
  const send = useSendWhatsapp(orderId)

  // Load the generated body when the event changes / preview arrives.
  useEffect(() => {
    if (preview.data) setBody(preview.data.message_body)
  }, [preview.data])

  // Reset transient state each time the drawer opens.
  useEffect(() => {
    if (open) {
      setEvent(events[0] ?? 'order_confirmed')
      setResult(null)
    }
  }, [open, events])

  const hasPhone = preview.data?.has_phone ?? false

  async function handleSend() {
    setResult(null)
    try {
      const res = await send.mutateAsync({ event_type: event, message_body: body })
      setResult(res)
    } catch (err: unknown) {
      const e = err as { message?: string; request_id?: string }
      setResult({ notification_id: 0, event_type: event, recipient_phone: null, status: 'failed', message_body: e?.message ?? 'Send failed' })
    }
  }

  return (
    <DrawerPanel open={open} onClose={onClose} title="Send WhatsApp" size="md">
      <div className="space-y-4">
        <label className="block">
          <span className="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">Message type</span>
          <select
            value={event}
            onChange={(e) => {
              setEvent(e.target.value as WhatsappEvent)
              setResult(null)
            }}
            className="w-full h-10 px-3 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
          >
            {events.map((ev) => (
              <option key={ev} value={ev}>
                {WHATSAPP_EVENT_LABELS[ev]}
              </option>
            ))}
          </select>
        </label>

        <div className="text-xs text-[var(--color-text-muted)]">
          To: {preview.data?.recipient_phone ?? '—'}
          {preview.data && !preview.data.provider_configured && (
            <span className="ml-2 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-amber-800">
              <Info size={11} strokeWidth={1.75} /> Provider not configured — will be simulated
            </span>
          )}
        </div>

        {!hasPhone && preview.data && (
          <div className="flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
            <AlertTriangle size={13} strokeWidth={1.75} /> Customer has no phone number — cannot send.
          </div>
        )}

        <label className="block">
          <span className="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">Message</span>
          {preview.isFetching ? (
            <div className="flex items-center gap-2 py-6 text-sm text-[var(--color-text-muted)]">
              <Loader2 size={15} className="animate-spin" /> Generating preview…
            </div>
          ) : (
            <textarea
              value={body}
              onChange={(e) => setBody(e.target.value)}
              rows={5}
              className="w-full px-3 py-2.5 text-sm border border-[var(--color-border-mid)] rounded-lg bg-white resize-none focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          )}
        </label>

        {result && (
          <div className={cn('flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm', STATUS_COPY[result.status].cls)}>
            {result.status === 'failed' ? <AlertTriangle size={15} strokeWidth={1.75} /> : <CheckCircle2 size={15} strokeWidth={1.75} />}
            {STATUS_COPY[result.status].label}
          </div>
        )}

        <div className="flex justify-end gap-2 pt-1">
          <button
            type="button"
            onClick={onClose}
            className="px-4 h-10 rounded-lg border border-[var(--color-border-mid)] text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
          >
            Close
          </button>
          <button
            type="button"
            onClick={handleSend}
            disabled={!hasPhone || !body.trim() || send.isPending}
            className="inline-flex items-center gap-1.5 px-5 h-10 rounded-lg bg-[var(--color-brand)] text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
          >
            {send.isPending ? <Loader2 size={15} className="animate-spin" /> : <MessageCircle size={15} strokeWidth={1.75} />}
            {result?.status === 'failed' ? 'Retry' : 'Send'}
          </button>
        </div>
      </div>
    </DrawerPanel>
  )
}
