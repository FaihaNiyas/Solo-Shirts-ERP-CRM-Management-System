'use client'

import { useState, useEffect, useRef } from 'react'
import { toast } from 'sonner'
import { useMutation, useQuery } from '@tanstack/react-query'
import { apiGet, apiPost } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { PageHeader } from '@/components/ui/page-header'
import { FormField } from '@/components/ui/form-field'
import { FileText, Download, RefreshCw } from 'lucide-react'
import { format } from 'date-fns'

function humanize(kind: string): string {
  return kind.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

interface DocumentRef {
  id: number
  download_url?: string
}

interface ReportJob {
  id: number
  kind?: string
  // Backend lifecycle: pending → running → succeeded | failed
  status?: 'pending' | 'running' | 'succeeded' | 'failed'
  error?: string | null
  requested_at?: string
  completed_at?: string
  document?: DocumentRef | null
}

export default function ReportsPage() {
  const [kind, setKind] = useState('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')
  const [activeJobId, setActiveJobId] = useState<number | null>(null)
  // No backend job-history endpoint exists, so we keep the jobs created this
  // session locally rather than inventing a route.
  const [sessionJobs, setSessionJobs] = useState<ReportJob[]>([])
  const pollingRef = useRef<ReturnType<typeof setInterval> | null>(null)

  // Available report kinds come from the backend registry.
  const { data: kinds = [] } = useQuery<string[]>({
    queryKey: ['report-kinds'],
    queryFn: () =>
      apiGet<{ kinds: string[] }>(ENDPOINTS.reports).then((r) => r.data.kinds),
  })

  useEffect(() => {
    if (!kind && kinds.length > 0) setKind(kinds[0])
  }, [kinds, kind])

  const { data: activeJob, refetch: refetchJob } = useQuery<ReportJob>({
    queryKey: ['report-job', activeJobId],
    queryFn: () => apiGet<ReportJob>(ENDPOINTS.reportJob(activeJobId as number)).then((r) => r.data),
    enabled: activeJobId !== null,
    refetchInterval: false,
  })

  const generateMutation = useMutation({
    mutationFn: (body: Record<string, unknown>) =>
      apiPost<ReportJob>(ENDPOINTS.runReport, body),
    onSuccess: (res) => {
      setActiveJobId(res.data.id)
      setSessionJobs((prev) => [res.data, ...prev])
      toast.info('Report queued — generating…')
    },
    onError: (err: unknown) => {
      toast.error((err as { message?: string })?.message ?? 'Failed to queue report')
    },
  })

  // Poll at 3s while pending/running.
  useEffect(() => {
    if (activeJobId === null) return
    if (activeJob?.status === 'succeeded' || activeJob?.status === 'failed') {
      if (pollingRef.current) clearInterval(pollingRef.current)
      setSessionJobs((prev) => prev.map((j) => (j.id === activeJob.id ? activeJob : j)))
      if (activeJob.status === 'succeeded') toast.success('Report ready — download below')
      if (activeJob.status === 'failed') toast.error(activeJob.error ?? 'Report generation failed')
      return
    }
    pollingRef.current = setInterval(() => { void refetchJob() }, 3_000)
    return () => { if (pollingRef.current) clearInterval(pollingRef.current) }
  }, [activeJobId, activeJob, refetchJob])

  function handleGenerate() {
    const params: Record<string, unknown> = {}
    if (dateFrom) params.date_from = dateFrom
    if (dateTo) params.date_to = dateTo
    generateMutation.mutate({ kind, params })
  }

  const isPolling = activeJob?.status === 'pending' || activeJob?.status === 'running'

  return (
    <div className="space-y-6">
      <PageHeader title="Reports" />

      <div className="rounded-xl border border-[var(--color-border)] bg-white p-6 space-y-4">
        <h2 className="text-sm font-semibold text-[var(--color-text-primary)]">Generate Report</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <FormField label="Report Type">
            <select
              value={kind}
              onChange={(e) => setKind(e.target.value)}
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] bg-white"
            >
              {kinds.map((k) => (
                <option key={k} value={k}>
                  {humanize(k)}
                </option>
              ))}
            </select>
          </FormField>
          <FormField label="Date From">
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </FormField>
          <FormField label="Date To">
            <input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </FormField>
        </div>
        <button
          onClick={handleGenerate}
          disabled={!kind || generateMutation.isPending || isPolling}
          className="flex items-center gap-2 px-5 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
        >
          {isPolling && <RefreshCw size={14} className="animate-spin" />}
          {isPolling ? 'Generating…' : 'Generate'}
        </button>

        {activeJob && (
          <div className={`flex items-center gap-3 p-3 rounded-lg border text-sm ${
            activeJob.status === 'succeeded'
              ? 'bg-green-50 border-green-200 text-green-700'
              : activeJob.status === 'failed'
              ? 'bg-red-50 border-red-200 text-red-700'
              : 'bg-amber-50 border-amber-200 text-amber-700'
          }`}>
            {isPolling && <RefreshCw size={14} className="animate-spin shrink-0" />}
            <span className="flex-1">
              {activeJob.status === 'pending' && 'Report queued…'}
              {activeJob.status === 'running' && 'Generating report…'}
              {activeJob.status === 'succeeded' && 'Report ready'}
              {activeJob.status === 'failed' && (activeJob.error ?? 'Generation failed')}
            </span>
            {activeJob.status === 'succeeded' && activeJob.document?.download_url && (
              <a
                href={activeJob.document.download_url}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-green-400 text-green-700 rounded-lg hover:bg-green-100 transition-colors"
              >
                <Download size={13} /> Download
              </a>
            )}
          </div>
        )}
      </div>

      {sessionJobs.length > 0 && (
        <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
          <div className="px-4 py-3 bg-[var(--color-surface-alt)] border-b border-[var(--color-border)]">
            <p className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">
              This Session
            </p>
          </div>
          <div className="divide-y divide-[var(--color-border)] bg-white">
            {sessionJobs.map((job) => (
              <div key={job.id} className="flex items-center gap-4 px-4 py-3">
                <FileText size={16} strokeWidth={1.75} className="text-[var(--color-text-muted)] shrink-0" />
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-[var(--color-text-primary)]">
                    {job.kind ? humanize(job.kind) : 'Report'}
                  </p>
                  <p className="text-xs text-[var(--color-text-muted)]">
                    {job.requested_at ? format(new Date(job.requested_at), 'dd MMM yyyy, HH:mm') : '—'}
                  </p>
                </div>
                <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${
                  job.status === 'succeeded' ? 'bg-green-100 text-green-700' :
                  job.status === 'failed' ? 'bg-red-100 text-red-700' :
                  'bg-amber-100 text-amber-700'
                }`}>
                  {job.status}
                </span>
                {job.status === 'succeeded' && job.document?.download_url && (
                  <a
                    href={job.document.download_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-1 px-2 py-1 text-xs border border-[var(--color-brand)] text-[var(--color-brand)] rounded hover:bg-[var(--color-brand-light)] transition-colors"
                  >
                    <Download size={12} /> Download
                  </a>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
