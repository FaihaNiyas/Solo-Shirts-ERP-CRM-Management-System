'use client'

import { useState } from 'react'
import { toast } from 'sonner'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiPost, apiPut, generateIdempotencyKey } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { PageHeader } from '@/components/ui/page-header'
import { StatusBadge } from '@/components/ui/status-badge'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { useAuth } from '@/lib/auth/useAuth'
import { Shield, Building2 } from 'lucide-react'

interface Branch {
  id: number
  code?: string
  name?: string
  address?: string
  phone?: string
  gst_number?: string
  is_active?: boolean
}

const emptyForm = { code: '', name: '', address: '', phone: '', gst_number: '' }

export default function AdminBranchesPage() {
  const qc = useQueryClient()
  const { user } = useAuth()
  const [drawerOpen, setDrawerOpen] = useState(false)
  const [editing, setEditing] = useState<Branch | null>(null)
  const [form, setForm] = useState(emptyForm)

  const isAllowed = user?.roles?.includes('Owner')

  const { data: branches = [], isLoading } = useQuery<Branch[]>({
    queryKey: ['admin', 'branches'],
    queryFn: () => apiGet<Branch[]>(ENDPOINTS.branches).then((r) => r.data),
    enabled: isAllowed,
  })

  const createMutation = useMutation({
    mutationFn: (body: Record<string, unknown>) =>
      apiPost<Branch>(ENDPOINTS.branches, body, {
        headers: { 'Idempotency-Key': generateIdempotencyKey() },
      }),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['admin', 'branches'] })
      closeDrawer()
      toast.success('Branch created')
    },
    onError: (err: unknown) => toast.error((err as { message?: string })?.message ?? 'Failed'),
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, body }: { id: number; body: Record<string, unknown> }) =>
      apiPut<Branch>(ENDPOINTS.branch(id), body, {
        headers: { 'Idempotency-Key': generateIdempotencyKey() },
      }),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['admin', 'branches'] })
      closeDrawer()
      toast.success('Branch updated')
    },
    onError: (err: unknown) => toast.error((err as { message?: string })?.message ?? 'Failed'),
  })

  function openCreate() {
    setEditing(null)
    setForm(emptyForm)
    setDrawerOpen(true)
  }

  function openEdit(branch: Branch) {
    setEditing(branch)
    setForm({
      code: branch.code ?? '',
      name: branch.name ?? '',
      address: branch.address ?? '',
      phone: branch.phone ?? '',
      gst_number: branch.gst_number ?? '',
    })
    setDrawerOpen(true)
  }

  function closeDrawer() {
    setDrawerOpen(false)
    setEditing(null)
    setForm(emptyForm)
  }

  function handleSubmit() {
    const body = { ...form }
    if (editing) {
      updateMutation.mutate({ id: editing.id, body })
    } else {
      createMutation.mutate(body)
    }
  }

  const isPending = createMutation.isPending || updateMutation.isPending

  if (!isAllowed) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center">
        <Shield size={40} strokeWidth={1.25} className="text-[var(--color-text-muted)]" />
        <p className="text-sm text-[var(--color-text-muted)]">Owner access required</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Branches"
        actions={
          <button
            onClick={openCreate}
            className="px-4 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-xl hover:bg-[var(--color-brand-dark)] transition-colors"
          >
            Add Branch
          </button>
        }
      />

      {isLoading && <TableSkeleton rows={4} cols={5} />}

      {!isLoading && branches.length === 0 && (
        <div className="flex flex-col items-center justify-center py-16 gap-3">
          <Building2 size={32} strokeWidth={1.25} className="text-[var(--color-text-muted)]" />
          <p className="text-sm text-[var(--color-text-muted)]">No branches configured</p>
        </div>
      )}

      {!isLoading && branches.length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {branches.map((branch) => (
            <div
              key={branch.id}
              className="rounded-xl border border-[var(--color-border)] bg-white p-4 space-y-3"
            >
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm font-semibold text-[var(--color-text-primary)]">
                    {branch.name}
                  </p>
                  <p className="font-mono text-xs text-[var(--color-text-muted)]">{branch.code}</p>
                </div>
                <StatusBadge status={branch.is_active ? 'active' : 'inactive'} />
              </div>
              {branch.address && (
                <p className="text-xs text-[var(--color-text-muted)]">{branch.address}</p>
              )}
              {branch.gst_number && (
                <p className="font-mono text-xs text-[var(--color-text-muted)]">
                  GSTIN: {branch.gst_number}
                </p>
              )}
              {branch.phone && (
                <p className="text-xs text-[var(--color-text-muted)]">{branch.phone}</p>
              )}
              <button
                onClick={() => openEdit(branch)}
                className="w-full py-1.5 text-xs font-medium border border-[var(--color-border)] rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
              >
                Edit
              </button>
            </div>
          ))}
        </div>
      )}

      <DrawerPanel
        open={drawerOpen}
        onClose={closeDrawer}
        title={editing ? 'Edit Branch' : 'Add Branch'}
        size="md"
      >
        <div className="space-y-4 p-4">
          <FormField label="Branch Code" required>
            <input
              value={form.code}
              onChange={(e) => setForm((f) => ({ ...f, code: e.target.value.toUpperCase() }))}
              maxLength={20}
              placeholder="e.g. HQ, MUM-01"
              className="w-full h-9 px-3 text-sm font-mono border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </FormField>
          <FormField label="Branch Name" required>
            <input
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </FormField>
          <FormField label="Address">
            <textarea
              value={form.address}
              onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))}
              rows={2}
              className="w-full px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] resize-none"
            />
          </FormField>
          <FormField label="Phone">
            <input
              type="tel"
              value={form.phone}
              onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </FormField>
          <FormField label="GSTIN">
            <input
              value={form.gst_number}
              onChange={(e) => setForm((f) => ({ ...f, gst_number: e.target.value.toUpperCase() }))}
              maxLength={20}
              className="w-full h-9 px-3 text-sm font-mono border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              placeholder="15-digit GSTIN"
            />
          </FormField>
          <div className="flex gap-2">
            <button
              onClick={handleSubmit}
              disabled={!form.name || !form.code || isPending}
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {isPending ? 'Saving…' : editing ? 'Update' : 'Create'}
            </button>
            <button
              onClick={closeDrawer}
              className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </DrawerPanel>
    </div>
  )
}
