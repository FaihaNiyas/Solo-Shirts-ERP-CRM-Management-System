'use client'

import { useEffect, useRef, useState } from 'react'
import { toast } from 'sonner'
import {
  FileText,
  Printer,
  Loader2,
  AlertTriangle,
  ExternalLink,
  RotateCw,
  Check,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { useWizard } from '../WizardContext'
import type { SubOrderDraft } from '../types'
import { generateItemPdf, logPrint } from '../printCenterApi'

export function StepPrintCenter() {
  const { orderId, creating, createError, ensureOrderCreated, subOrders } = useWizard()
  const started = useRef(false)

  // Create the order once on entry so PDF/print can target real sub-orders.
  useEffect(() => {
    if (orderId || started.current) return
    started.current = true
    void ensureOrderCreated()
  }, [orderId, ensureOrderCreated])

  if (!orderId) {
    return (
      <div className="flex flex-col items-center justify-center rounded-xl border border-[var(--color-border)] bg-white py-16 text-center">
        {creating && (
          <>
            <Loader2 size={26} className="animate-spin text-[var(--color-brand)] mb-3" />
            <p className="text-sm font-medium text-[var(--color-text-primary)]">Creating the order…</p>
            <p className="text-xs text-[var(--color-text-muted)]">Job-card PDFs attach to each sub-order next.</p>
          </>
        )}
        {!creating && createError && (
          <>
            <AlertTriangle size={26} className="text-[var(--color-danger)] mb-3" />
            <p className="text-sm font-medium text-[var(--color-text-primary)]">{createError}</p>
            <button
              onClick={() => {
                started.current = true
                void ensureOrderCreated()
              }}
              className="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-4 h-9 text-sm font-medium text-white hover:bg-[var(--color-brand-dark)]"
            >
              <RotateCw size={14} /> Retry
            </button>
          </>
        )}
      </div>
    )
  }

  return <PrintCenter orderId={orderId} subOrders={subOrders} />
}

function PrintCenter({ orderId, subOrders }: { orderId: number; subOrders: SubOrderDraft[] }) {
  const { patchSubOrder } = useWizard()
  const [bulkBusy, setBulkBusy] = useState(false)

  function fail(err: unknown) {
    const e = err as { message?: string; request_id?: string }
    toast.error(e?.message ?? 'Action failed', { description: e?.request_id ? `request_id: ${e.request_id}` : undefined })
  }

  async function bulkGeneratePdfs() {
    setBulkBusy(true)
    try {
      for (const s of subOrders) {
        if (!s.itemId || s.pdfStatus === 'generated') continue
        const r = await generateItemPdf(orderId, s.itemId)
        patchSubOrder(s.tempId, { pdfStatus: 'generated', documentId: r.id, pdfUrl: r.download_url })
      }
    } catch (err) {
      fail(err)
    } finally {
      setBulkBusy(false)
    }
  }

  async function bulkMarkPrinted() {
    setBulkBusy(true)
    try {
      for (const s of subOrders) {
        if (!s.itemId) continue
        if (s.pdfStatus === 'generated' && s.printStatus !== 'printed') {
          await logPrint(orderId, s.itemId, false, s.documentId)
          patchSubOrder(s.tempId, { printStatus: 'printed' })
        }
      }
    } catch (err) {
      fail(err)
    } finally {
      setBulkBusy(false)
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-2">
        <BulkBtn onClick={bulkGeneratePdfs} disabled={bulkBusy} icon={FileText} label="Generate All PDFs" />
        <BulkBtn onClick={bulkMarkPrinted} disabled={bulkBusy} icon={Printer} label="Mark All Printed" />
        {bulkBusy && <Loader2 size={16} className="animate-spin text-[var(--color-brand)]" />}
      </div>

      <div className="space-y-2">
        {subOrders.map((s, i) => (
          <PrintRow key={s.tempId} orderId={orderId} sub={s} index={i} onFail={fail} />
        ))}
      </div>

      <p className="text-xs text-[var(--color-text-muted)]">
        Generate and print each sub-order&apos;s job card. The order code and item code are printed on every sheet.
      </p>
    </div>
  )
}

function PrintRow({
  orderId,
  sub,
  index,
  onFail,
}: {
  orderId: number
  sub: SubOrderDraft
  index: number
  onFail: (err: unknown) => void
}) {
  const { patchSubOrder } = useWizard()
  const [busy, setBusy] = useState<string | null>(null)
  const code = String(index + 1).padStart(2, '0')
  const itemId = sub.itemId

  async function run(tag: string, fn: () => Promise<void>) {
    if (!itemId) return
    setBusy(tag)
    try {
      await fn()
    } catch (err) {
      onFail(err)
    } finally {
      setBusy(null)
    }
  }

  const doGenerate = () =>
    run('pdf', async () => {
      const r = await generateItemPdf(orderId, itemId!)
      patchSubOrder(sub.tempId, { pdfStatus: 'generated', documentId: r.id, pdfUrl: r.download_url })
    })

  const doPrint = (reprint: boolean) =>
    run('print', async () => {
      if (sub.pdfUrl) window.open(sub.pdfUrl, '_blank', 'noopener')
      await logPrint(orderId, itemId!, reprint, sub.documentId)
      if (!reprint) patchSubOrder(sub.tempId, { printStatus: 'printed' })
    })

  return (
    <div className="rounded-xl border border-[var(--color-border)] bg-white p-3">
      <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
        <span className="ss-mono text-sm font-semibold text-[var(--color-text-primary)] w-16 shrink-0">{code}</span>

        {/* PDF */}
        <div className="flex items-center gap-1.5">
          {sub.pdfStatus === 'generated' ? (
            <Chip ok icon={FileText} text="PDF ready" />
          ) : (
            <button
              onClick={doGenerate}
              disabled={busy !== null}
              className="inline-flex items-center gap-1 rounded-lg border border-[var(--color-brand)] text-[var(--color-brand)] px-2.5 h-8 text-xs font-medium hover:bg-[var(--color-brand-light)] disabled:opacity-40"
            >
              {busy === 'pdf' ? <Loader2 size={12} className="animate-spin" /> : <FileText size={12} />} Generate PDF
            </button>
          )}
        </div>

        {/* Print */}
        <div className="ml-auto flex items-center gap-1.5">
          {sub.printStatus === 'printed' ? (
            <>
              <Chip ok icon={Printer} text="Printed" />
              <button
                onClick={() => doPrint(true)}
                disabled={busy !== null}
                className="inline-flex items-center gap-1 rounded-lg border border-[var(--color-border-mid)] px-2 h-8 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-40"
              >
                <RotateCw size={12} /> Reprint
              </button>
            </>
          ) : (
            <button
              onClick={() => doPrint(false)}
              disabled={busy !== null || sub.pdfStatus !== 'generated'}
              title={sub.pdfStatus === 'generated' ? undefined : 'Generate the PDF first'}
              className="inline-flex items-center gap-1 rounded-lg border border-[var(--color-border-mid)] px-2.5 h-8 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-40"
            >
              {busy === 'print' ? <Loader2 size={12} className="animate-spin" /> : <ExternalLink size={12} />} Print
            </button>
          )}
        </div>
      </div>
    </div>
  )
}

function Chip({ ok, icon: Icon, text }: { ok: boolean; icon: React.ElementType; text: string }) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium',
        ok ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800',
      )}
    >
      {ok ? <Check size={12} strokeWidth={2.5} /> : <Icon size={12} />}
      {text}
    </span>
  )
}

function BulkBtn({
  onClick,
  disabled,
  icon: Icon,
  label,
}: {
  onClick: () => void
  disabled: boolean
  icon: React.ElementType
  label: string
}) {
  return (
    <button
      onClick={onClick}
      disabled={disabled}
      className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-9 text-xs font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-50 transition-colors"
    >
      <Icon size={14} strokeWidth={1.75} /> {label}
    </button>
  )
}
