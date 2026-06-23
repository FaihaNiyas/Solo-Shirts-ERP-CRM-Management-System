'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { ModalDialog } from '@/components/ui/modal-dialog'
import { FormField, Textarea, Button } from '@/components/ui/form-field'
import { useReportIssue } from '@/lib/api/hooks/useProduction'
import { ISSUE_TYPES, ISSUE_TYPE_LABELS } from '@/lib/api/schemas/production'

interface Props {
  open: boolean
  onClose: () => void
  itemId: number
  customerName?: string | null
}

const SELECT_CLS =
  'w-full h-10 px-3 rounded-lg border border-[var(--color-border-mid)] text-sm bg-white ' +
  'text-[var(--color-text-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]'

/** Raise a text-only production issue against an item (Kanban Phase B). */
export function ReportIssueModal({ open, onClose, itemId, customerName }: Props) {
  const report = useReportIssue(itemId)
  const [type, setType] = useState<string>(ISSUE_TYPES[0])
  const [description, setDescription] = useState('')

  async function submit() {
    if (!description.trim()) return
    try {
      await report.mutateAsync({ issue_type: type, description: description.trim() })
      toast.success('Issue reported')
      setDescription('')
      onClose()
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Could not report the issue')
    }
  }

  return (
    <ModalDialog
      open={open}
      onClose={onClose}
      title="Report an issue"
      description={customerName ?? undefined}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={report.isPending}>
            Cancel
          </Button>
          <Button onClick={submit} disabled={report.isPending || !description.trim()} loading={report.isPending}>
            Report issue
          </Button>
        </>
      }
    >
      <div className="space-y-4">
        <FormField label="Issue type" required>
          <select value={type} onChange={(e) => setType(e.target.value)} className={SELECT_CLS}>
            {ISSUE_TYPES.map((t) => (
              <option key={t} value={t}>
                {ISSUE_TYPE_LABELS[t]}
              </option>
            ))}
          </select>
        </FormField>
        <FormField label="Description" required>
          <Textarea
            rows={3}
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            placeholder="Describe the problem…"
          />
        </FormField>
      </div>
    </ModalDialog>
  )
}
