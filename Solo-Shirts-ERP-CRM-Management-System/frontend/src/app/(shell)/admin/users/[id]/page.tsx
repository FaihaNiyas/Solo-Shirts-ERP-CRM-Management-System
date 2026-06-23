'use client'

import { use, useEffect, useState } from 'react'
import { toast } from 'sonner'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiPost, apiPut, generateIdempotencyKey } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { PageHeader } from '@/components/ui/page-header'
import { FormField } from '@/components/ui/form-field'
import { StatusBadge } from '@/components/ui/status-badge'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { useAuth } from '@/lib/auth/useAuth'
import { format } from 'date-fns'

const ROLES = [
  'Owner', 'Admin', 'Front Desk', 'Measurement Staff', 'Production Supervisor',
  'Cutting Master', 'Tailor', 'Kaja Button', 'QC Supervisor', 'Ironing Master',
  'Re-Worker', 'Inventory Manager', 'Accountant', 'Delivery Staff',
]

interface BranchRef { id: number; name: string; code: string; is_active: boolean }

interface UserDetail {
  id: number
  name?: string
  email?: string
  phone?: string
  roles?: string[]
  branch?: BranchRef | null
  branch_id?: number | null
  is_active?: boolean
  two_factor_enabled?: boolean
  created_at?: string
}

const inputClass = 'w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] bg-white'

export default function AdminUserDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const userId = parseInt(id)
  const qc = useQueryClient()
  const { user: currentUser } = useAuth()

  const [editMode, setEditMode] = useState(false)
  const [showDeactivate, setShowDeactivate] = useState(false)
  const [showActivate, setShowActivate] = useState(false)

  // Edit form state
  const [form, setForm] = useState({
    name: '', email: '', phone: '', role: '',
    branch_id: 0, is_active: true,
    newPassword: '', confirmPassword: '',
  })

  const { data: user, isLoading } = useQuery<UserDetail>({
    queryKey: ['admin', 'user', userId],
    queryFn: () => apiGet<UserDetail>(ENDPOINTS.user(userId)).then((r) => r.data),
  })

  // Active branches for dropdown
  const { data: branches = [] } = useQuery<BranchRef[]>({
    queryKey: ['branches', 'active'],
    queryFn: () => apiGet<BranchRef[]>(ENDPOINTS.branchesActiveList).then((r) => r.data),
    enabled: editMode,
  })

  // Populate form when user loads or edit mode opens
  useEffect(() => {
    if (user && editMode) {
      setForm({
        name: user.name ?? '',
        email: user.email ?? '',
        phone: user.phone ?? '',
        role: user.roles?.[0] ?? '',
        branch_id: user.branch?.id ?? user.branch_id ?? 0,
        is_active: user.is_active ?? true,
        newPassword: '',
        confirmPassword: '',
      })
    }
  }, [user, editMode])

  const passwordMismatch = form.newPassword.length > 0 && form.newPassword !== form.confirmPassword
  const canSave = form.name.trim() !== '' && form.email.trim() !== '' && !passwordMismatch &&
    (form.newPassword === '' || form.newPassword.length >= 8)

  const updateMutation = useMutation({
    mutationFn: async () => {
      const currentRole = user?.roles?.[0] ?? ''
      const payload: Record<string, unknown> = {
        name: form.name,
        email: form.email,
        phone: form.phone || null,
        branch_id: form.branch_id || undefined,
        is_active: form.is_active,
      }
      if (form.newPassword) payload.password = form.newPassword

      // Update user details
      await apiPut<UserDetail>(ENDPOINTS.user(userId), payload, {
        headers: { 'Idempotency-Key': generateIdempotencyKey() },
      })

      // Assign role separately if changed (it also revokes tokens)
      if (form.role && form.role !== currentRole) {
        await apiPost<UserDetail>(ENDPOINTS.userAssignRole(userId), { role: form.role }, {
          headers: { 'Idempotency-Key': generateIdempotencyKey() },
        })
      }
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['admin', 'user', userId] })
      void qc.invalidateQueries({ queryKey: ['admin', 'users'] })
      setEditMode(false)
      toast.success('User updated')
    },
    onError: (err: unknown) => toast.error((err as { message?: string })?.message ?? 'Failed'),
  })

  const deactivateMutation = useMutation({
    mutationFn: () => apiPost<UserDetail>(ENDPOINTS.userDeactivate(userId), {}, {
      headers: { 'Idempotency-Key': generateIdempotencyKey() },
    }),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['admin', 'user', userId] })
      void qc.invalidateQueries({ queryKey: ['admin', 'users'] })
      setShowDeactivate(false)
      toast.success('User deactivated')
    },
    onError: (err: unknown) => toast.error((err as { message?: string })?.message ?? 'Failed'),
  })

  const activateMutation = useMutation({
    mutationFn: () => apiPost<UserDetail>(ENDPOINTS.userActivate(userId), {}, {
      headers: { 'Idempotency-Key': generateIdempotencyKey() },
    }),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['admin', 'user', userId] })
      void qc.invalidateQueries({ queryKey: ['admin', 'users'] })
      setShowActivate(false)
      toast.success('User reactivated')
    },
    onError: (err: unknown) => toast.error((err as { message?: string })?.message ?? 'Failed'),
  })

  const canEdit = currentUser?.roles?.includes('Owner') || currentUser?.roles?.includes('Admin')
  const isSelf = currentUser?.id === userId

  if (isLoading) return <TableSkeleton rows={4} cols={4} />
  if (!user) return <p className="text-sm text-[var(--color-text-muted)]">User not found</p>

  return (
    <div className="space-y-6">
      <PageHeader
        title={user.name ?? 'User'}
        actions={
          canEdit && !isSelf ? (
            <div className="flex gap-2">
              <button
                onClick={() => {
                  if (editMode) setEditMode(false)
                  else setEditMode(true)
                }}
                className="px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg hover:bg-[var(--color-surface-alt)] transition-colors"
              >
                {editMode ? 'Cancel' : 'Edit User'}
              </button>
              {user.is_active ? (
                <button
                  onClick={() => setShowDeactivate(true)}
                  className="px-3 py-2 text-sm border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                >
                  Deactivate
                </button>
              ) : (
                <button
                  onClick={() => setShowActivate(true)}
                  className="px-3 py-2 text-sm border border-green-300 text-green-600 rounded-lg hover:bg-green-50 transition-colors"
                >
                  Reactivate
                </button>
              )}
            </div>
          ) : undefined
        }
      />

      {/* Info cards — always visible */}
      {!editMode && (
        <>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {[
              { label: 'Email',      value: user.email ?? '—' },
              { label: 'Phone',      value: user.phone ?? '—' },
              { label: 'Branch',     value: user.branch?.name ?? '—' },
              { label: 'Joined',     value: user.created_at ? format(new Date(user.created_at), 'dd MMM yyyy') : '—' },
            ].map(({ label, value }) => (
              <div key={label} className="rounded-xl border border-[var(--color-border)] bg-white p-4">
                <p className="text-xs text-[var(--color-text-muted)] mb-1">{label}</p>
                <p className="text-sm font-medium text-[var(--color-text-primary)] break-all">{value}</p>
              </div>
            ))}
          </div>

          <div className="flex items-center gap-6">
            <div className="flex items-center gap-2">
              <span className="text-sm text-[var(--color-text-muted)]">Status:</span>
              <StatusBadge status={user.is_active ? 'active' : 'inactive'} />
            </div>
            <div className="flex items-center gap-2">
              <span className="text-sm text-[var(--color-text-muted)]">Role:</span>
              <span className="px-2 py-0.5 text-xs font-medium bg-[var(--color-brand-light)] text-[var(--color-brand)] rounded-full">
                {user.roles?.[0] ?? '—'}
              </span>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-sm text-[var(--color-text-muted)]">2FA:</span>
              <span className="text-sm">{user.two_factor_enabled ? 'Enabled' : 'Disabled'}</span>
            </div>
          </div>
        </>
      )}

      {/* Edit form */}
      {editMode && (
        <div className="rounded-xl border border-[var(--color-border)] bg-white p-6 space-y-4">
          <p className="text-sm font-semibold text-[var(--color-text-primary)] mb-2">Edit User Details</p>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField label="Full Name" required id="edit-name">
              <input id="edit-name" value={form.name}
                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                className={inputClass} />
            </FormField>

            <FormField label="Email" required id="edit-email">
              <input id="edit-email" type="email" value={form.email}
                onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                className={inputClass} />
            </FormField>

            <FormField label="Phone" id="edit-phone">
              <input id="edit-phone" type="tel" value={form.phone}
                onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                className={inputClass} />
            </FormField>

            <FormField label="Branch" id="edit-branch">
              <select id="edit-branch" value={form.branch_id || ''}
                onChange={(e) => setForm((f) => ({ ...f, branch_id: Number(e.target.value) }))}
                className={inputClass}>
                <option value="" disabled>Select branch…</option>
                {branches.map((b) => (
                  <option key={b.id} value={b.id}>{b.name} ({b.code})</option>
                ))}
              </select>
            </FormField>

            <FormField label="Role" id="edit-role">
              <select id="edit-role" value={form.role}
                onChange={(e) => setForm((f) => ({ ...f, role: e.target.value }))}
                className={inputClass}>
                {ROLES.map((r) => <option key={r}>{r}</option>)}
              </select>
            </FormField>

            <FormField label="Status" id="edit-status">
              <select id="edit-status" value={form.is_active ? 'active' : 'inactive'}
                onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.value === 'active' }))}
                className={inputClass}>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </FormField>
          </div>

          {/* Optional password change */}
          <div className="border-t border-[var(--color-border)] pt-4 space-y-4">
            <p className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">
              Change Password — leave blank to keep current
            </p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField label="New Password" id="edit-password">
                <input id="edit-password" type="password" value={form.newPassword}
                  placeholder="Min. 8 characters"
                  onChange={(e) => setForm((f) => ({ ...f, newPassword: e.target.value }))}
                  className={inputClass} />
                {form.newPassword && form.newPassword.length < 8 && (
                  <p className="mt-1 text-xs text-red-500">Minimum 8 characters</p>
                )}
              </FormField>

              <FormField label="Confirm New Password" id="edit-confirm-password">
                <input id="edit-confirm-password" type="password" value={form.confirmPassword}
                  placeholder="Re-enter new password"
                  onChange={(e) => setForm((f) => ({ ...f, confirmPassword: e.target.value }))}
                  className={`w-full h-9 px-3 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] bg-white ${
                    passwordMismatch ? 'border-red-400' : 'border-[var(--color-border)]'
                  }`} />
                {passwordMismatch && (
                  <p className="mt-1 text-xs text-red-500">Passwords do not match</p>
                )}
              </FormField>
            </div>
          </div>

          <div className="flex gap-2 pt-2">
            <button
              onClick={() => updateMutation.mutate()}
              disabled={!canSave || updateMutation.isPending}
              className="px-5 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {updateMutation.isPending ? 'Saving…' : 'Save Changes'}
            </button>
            <button
              onClick={() => setEditMode(false)}
              className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      )}

      <ConfirmDialog
        open={showDeactivate}
        onClose={() => setShowDeactivate(false)}
        onConfirm={async () => { await deactivateMutation.mutateAsync() }}
        title="Deactivate User"
        description={`${user.name ?? 'This user'} will lose access immediately and all sessions will be revoked.`}
        variant="destructive"
        loading={deactivateMutation.isPending}
      />

      <ConfirmDialog
        open={showActivate}
        onClose={() => setShowActivate(false)}
        onConfirm={async () => { await activateMutation.mutateAsync() }}
        title="Reactivate User"
        description={`${user.name ?? 'This user'} will be able to log in again.`}
        variant="info"
        loading={activateMutation.isPending}
      />
    </div>
  )
}
