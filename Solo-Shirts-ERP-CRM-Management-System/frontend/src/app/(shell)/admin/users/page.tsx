'use client'

import { useEffect, useState } from 'react'
import { toast } from 'sonner'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { apiGet, apiPost, apiPut, generateIdempotencyKey } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { PageHeader } from '@/components/ui/page-header'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { useAuth } from '@/lib/auth/useAuth'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { Shield, Pencil, Eye } from 'lucide-react'
import { format } from 'date-fns'

const ROLES = [
  'Owner', 'Admin', 'Front Desk', 'Measurement Staff', 'Production Supervisor',
  'Cutting Master', 'Tailor', 'Kaja Button', 'QC Supervisor', 'Ironing Master',
  'Re-Worker', 'Inventory Manager', 'Accountant', 'Delivery Staff',
]

interface BranchRef {
  id: number; name: string; code: string; is_active: boolean
}

interface User {
  id: number
  name?: string
  email?: string
  phone?: string
  roles?: string[]
  branch?: BranchRef | null
  branch_id?: number | null
  is_active?: boolean
  created_at?: string
}

const inputClass = 'w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] bg-white'
const emptyCreate = { name: '', email: '', phone: '', password: '', confirmPassword: '', role: 'Tailor', branch_id: 0 }
const emptyEdit = { name: '', email: '', phone: '', role: '', branch_id: 0, is_active: true, newPassword: '', confirmPassword: '' }

export default function AdminUsersPage() {
  const qc = useQueryClient()
  const { user: currentUser } = useAuth()

  // Drawer / dialog state
  const [showCreate, setShowCreate] = useState(false)
  const [showUser, setShowUser]     = useState<User | null>(null)
  const [editUser, setEditUser]     = useState<User | null>(null)
  const [confirmUser, setConfirmUser] = useState<User | null>(null)

  // Form state
  const [createForm, setCreateForm] = useState(emptyCreate)
  const [editForm, setEditForm]     = useState(emptyEdit)

  const isAllowed = currentUser?.roles?.includes('Owner') || currentUser?.roles?.includes('Admin')

  const { data: users = [], isLoading } = useQuery<User[]>({
    queryKey: ['admin', 'users'],
    queryFn: () => apiGet<User[]>(ENDPOINTS.users).then((r) => r.data),
    enabled: isAllowed,
  })

  const { data: activeBranches = [] } = useQuery<BranchRef[]>({
    queryKey: ['branches', 'active'],
    queryFn: () => apiGet<BranchRef[]>(ENDPOINTS.branchesActiveList).then((r) => r.data),
    enabled: isAllowed,
  })

  // Populate edit form when a user is selected for editing
  useEffect(() => {
    if (editUser) {
      setEditForm({
        name:           editUser.name ?? '',
        email:          editUser.email ?? '',
        phone:          editUser.phone ?? '',
        role:           editUser.roles?.[0] ?? '',
        branch_id:      editUser.branch?.id ?? editUser.branch_id ?? 0,
        is_active:      editUser.is_active ?? true,
        newPassword:    '',
        confirmPassword:'',
      })
    }
  }, [editUser])

  // ── Mutations ─────────────────────────────────────────────────────────────

  const createMutation = useMutation({
    mutationFn: (body: Record<string, unknown>) =>
      apiPost<User>(ENDPOINTS.users, body, {
        headers: { 'Idempotency-Key': generateIdempotencyKey() },
      }),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['admin', 'users'] })
      setShowCreate(false)
      setCreateForm(emptyCreate)
      toast.success('User created')
    },
    onError: (err: unknown) => toast.error((err as { message?: string })?.message ?? 'Failed'),
  })

  const updateMutation = useMutation({
    mutationFn: async () => {
      if (!editUser) return
      const currentRole = editUser.roles?.[0] ?? ''
      const payload: Record<string, unknown> = {
        name:      editForm.name,
        email:     editForm.email,
        phone:     editForm.phone || null,
        branch_id: editForm.branch_id || undefined,
        is_active: editForm.is_active,
      }
      if (editForm.newPassword) payload.password = editForm.newPassword

      await apiPut<User>(ENDPOINTS.user(editUser.id), payload, {
        headers: { 'Idempotency-Key': generateIdempotencyKey() },
      })

      if (editForm.role && editForm.role !== currentRole) {
        await apiPost<User>(ENDPOINTS.userAssignRole(editUser.id), { role: editForm.role }, {
          headers: { 'Idempotency-Key': generateIdempotencyKey() },
        })
      }
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['admin', 'users'] })
      setEditUser(null)
      toast.success('User updated')
    },
    onError: (err: unknown) => toast.error((err as { message?: string })?.message ?? 'Failed'),
  })

  const toggleActive = useMutation({
    mutationFn: (u: User) =>
      apiPost<User>(u.is_active ? ENDPOINTS.userDeactivate(u.id) : ENDPOINTS.userActivate(u.id), {}),
    onSuccess: (_data, u) => {
      void qc.invalidateQueries({ queryKey: ['admin', 'users'] })
      toast.success(u.is_active ? 'User deactivated' : 'User activated')
    },
    onError: (err: unknown) => toast.error((err as { message?: string })?.message ?? 'Failed'),
  })

  // ── Derived ───────────────────────────────────────────────────────────────

  const passwordMismatch = editForm.newPassword.length > 0 && editForm.newPassword !== editForm.confirmPassword
  const canSave =
    editForm.name.trim() !== '' &&
    editForm.email.trim() !== '' &&
    !passwordMismatch &&
    (editForm.newPassword === '' || editForm.newPassword.length >= 8) &&
    !updateMutation.isPending

  const createPasswordMismatch =
    createForm.confirmPassword.length > 0 && createForm.password !== createForm.confirmPassword

  // ── Guards ────────────────────────────────────────────────────────────────

  if (!isAllowed) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center">
        <Shield size={40} strokeWidth={1.25} className="text-[var(--color-text-muted)]" />
        <p className="text-sm text-[var(--color-text-muted)]">Owner or Admin access required</p>
      </div>
    )
  }

  // ── Render ────────────────────────────────────────────────────────────────

  return (
    <div className="space-y-6">
      <PageHeader
        title="Users"
        actions={
          <button
            onClick={() => setShowCreate(true)}
            className="px-4 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-xl hover:bg-[var(--color-brand-dark)] transition-colors"
          >
            Add User
          </button>
        }
      />

      {isLoading && <TableSkeleton rows={8} cols={5} />}
      {!isLoading && users.length === 0 && (
        <p className="py-12 text-center text-sm text-[var(--color-text-muted)]">No users yet</p>
      )}

      {!isLoading && users.length > 0 && (
        <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-[var(--color-surface-alt)]">
              <tr>
                {['#', 'Name', 'Email', 'Branch', 'Roles', 'Action'].map((h) => (
                  <th
                    key={h}
                    className={`px-4 py-3 text-left text-xs font-semibold text-[var(--color-text-muted)] ${h === 'Action' ? 'text-right' : ''}`}
                  >
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-[var(--color-border)]">
              {users.map((u, idx) => (
                <tr key={u.id} className="hover:bg-[var(--color-surface-alt)] transition-colors">
                  <td className="px-4 py-3 text-[var(--color-text-muted)] text-xs">{idx + 1}</td>
                  <td className="px-4 py-3 font-medium text-[var(--color-text-primary)]">{u.name ?? '—'}</td>
                  <td className="px-4 py-3 text-[var(--color-text-muted)]">{u.email ?? '—'}</td>
                  <td className="px-4 py-3 text-[var(--color-text-muted)]">{u.branch?.name ?? '—'}</td>
                  <td className="px-4 py-3">
                    {u.roles?.[0] ? (
                      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        {u.roles[0]}
                      </span>
                    ) : '—'}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-2">
                      {/* Show */}
                      <button
                        onClick={() => setShowUser(u)}
                        className="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
                        title="View user"
                      >
                        <Eye size={13} strokeWidth={1.75} />
                      </button>

                      {/* Edit */}
                      <button
                        onClick={() => setEditUser(u)}
                        className="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
                        title="Edit user"
                      >
                        <Pencil size={13} strokeWidth={1.75} />
                      </button>

                      {/* Active / Inactive */}
                      <button
                        onClick={() => setConfirmUser(u)}
                        disabled={toggleActive.isPending}
                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors disabled:opacity-50 ${
                          u.is_active
                            ? 'bg-green-50 border-green-200 text-green-700 hover:bg-green-100'
                            : 'bg-red-50 border-red-200 text-red-600 hover:bg-red-100'
                        }`}
                      >
                        <span className={`w-1.5 h-1.5 rounded-full ${u.is_active ? 'bg-green-500' : 'bg-red-400'}`} />
                        {u.is_active ? 'Active' : 'Inactive'}
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* ── Confirm dialog ───────────────────────────────────────────────── */}
      <ConfirmDialog
        open={confirmUser !== null}
        onClose={() => setConfirmUser(null)}
        onConfirm={() => { if (confirmUser) { toggleActive.mutate(confirmUser); setConfirmUser(null) } }}
        title={confirmUser?.is_active ? 'Deactivate user?' : 'Activate user?'}
        description={
          confirmUser?.is_active
            ? `${confirmUser.name ?? 'This user'} will lose access immediately and all sessions will be revoked.`
            : `${confirmUser?.name ?? 'This user'} will be able to log in again.`
        }
        variant={confirmUser?.is_active ? 'danger' : 'info'}
        confirmLabel={confirmUser?.is_active ? 'Yes, deactivate' : 'Yes, activate'}
        loading={toggleActive.isPending}
      />

      {/* ── Show drawer ──────────────────────────────────────────────────── */}
      <DrawerPanel open={showUser !== null} onClose={() => setShowUser(null)} title="User Details" size="md">
        {showUser && (
          <div className="p-4 space-y-4">
            <div className="flex items-center gap-3 pb-3 border-b border-[var(--color-border)]">
              <div className="w-10 h-10 rounded-full bg-[var(--color-brand)] flex items-center justify-center text-white text-sm font-bold shrink-0">
                {(showUser.name ?? '?').charAt(0).toUpperCase()}
              </div>
              <div>
                <p className="font-semibold text-[var(--color-text-primary)]">{showUser.name ?? '—'}</p>
                <p className="text-xs text-[var(--color-text-muted)]">{showUser.email ?? '—'}</p>
              </div>
              <div className="ml-auto">
                <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border ${
                  showUser.is_active
                    ? 'bg-green-50 border-green-200 text-green-700'
                    : 'bg-red-50 border-red-200 text-red-600'
                }`}>
                  <span className={`w-1.5 h-1.5 rounded-full ${showUser.is_active ? 'bg-green-500' : 'bg-red-400'}`} />
                  {showUser.is_active ? 'Active' : 'Inactive'}
                </span>
              </div>
            </div>

            {[
              { label: 'Phone',  value: showUser.phone ?? '—' },
              { label: 'Branch', value: showUser.branch?.name ?? '—' },
              { label: 'Role',   value: showUser.roles?.[0] ?? '—' },
              { label: 'Joined', value: showUser.created_at ? format(new Date(showUser.created_at), 'dd MMM yyyy') : '—' },
            ].map(({ label, value }) => (
              <div key={label} className="flex items-center justify-between py-2 border-b border-[var(--color-border)] last:border-0">
                <span className="text-xs text-[var(--color-text-muted)] w-24 shrink-0">{label}</span>
                <span className="text-sm text-[var(--color-text-primary)] font-medium text-right">{value}</span>
              </div>
            ))}

            <div className="pt-2 flex gap-2">
              <button
                onClick={() => { setShowUser(null); setEditUser(showUser) }}
                className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] transition-colors"
              >
                Edit User
              </button>
              <button
                onClick={() => setShowUser(null)}
                className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
              >
                Close
              </button>
            </div>
          </div>
        )}
      </DrawerPanel>

      {/* ── Edit drawer ──────────────────────────────────────────────────── */}
      <DrawerPanel open={editUser !== null} onClose={() => setEditUser(null)} title="Edit User" size="md">
        {editUser && (
          <div className="p-4 space-y-4">
            <FormField label="Full Name" required id="edit-name">
              <input id="edit-name" value={editForm.name}
                onChange={(e) => setEditForm((f) => ({ ...f, name: e.target.value }))}
                className={inputClass} />
            </FormField>

            <FormField label="Email" required id="edit-email">
              <input id="edit-email" type="email" value={editForm.email}
                onChange={(e) => setEditForm((f) => ({ ...f, email: e.target.value }))}
                className={inputClass} />
            </FormField>

            <FormField label="Phone" id="edit-phone">
              <input id="edit-phone" type="tel" value={editForm.phone}
                onChange={(e) => setEditForm((f) => ({ ...f, phone: e.target.value }))}
                className={inputClass} />
            </FormField>

            <FormField label="Branch" id="edit-branch">
              <select id="edit-branch" value={editForm.branch_id || ''}
                onChange={(e) => setEditForm((f) => ({ ...f, branch_id: Number(e.target.value) }))}
                className={inputClass}>
                <option value="" disabled>Select branch…</option>
                {activeBranches.map((b) => (
                  <option key={b.id} value={b.id}>{b.name} ({b.code})</option>
                ))}
              </select>
            </FormField>

            <FormField label="Role" id="edit-role">
              <select id="edit-role" value={editForm.role}
                onChange={(e) => setEditForm((f) => ({ ...f, role: e.target.value }))}
                className={inputClass}>
                {ROLES.map((r) => <option key={r}>{r}</option>)}
              </select>
            </FormField>

            <FormField label="Status" id="edit-status">
              <select id="edit-status" value={editForm.is_active ? 'active' : 'inactive'}
                onChange={(e) => setEditForm((f) => ({ ...f, is_active: e.target.value === 'active' }))}
                className={inputClass}>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </FormField>

            {/* Password change — optional */}
            <div className="border-t border-[var(--color-border)] pt-4 space-y-3">
              <p className="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">
                Change Password — leave blank to keep current
              </p>

              <FormField label="New Password" id="edit-new-password">
                <input id="edit-new-password" type="password" value={editForm.newPassword}
                  placeholder="Min. 8 characters"
                  onChange={(e) => setEditForm((f) => ({ ...f, newPassword: e.target.value }))}
                  className={inputClass} />
                {editForm.newPassword.length > 0 && editForm.newPassword.length < 8 && (
                  <p className="mt-1 text-xs text-red-500">Minimum 8 characters</p>
                )}
              </FormField>

              <FormField label="Confirm Password" id="edit-confirm-password">
                <input id="edit-confirm-password" type="password" value={editForm.confirmPassword}
                  placeholder="Re-enter new password"
                  onChange={(e) => setEditForm((f) => ({ ...f, confirmPassword: e.target.value }))}
                  className={`w-full h-9 px-3 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] bg-white ${
                    passwordMismatch ? 'border-red-400' : 'border-[var(--color-border)]'
                  }`} />
                {passwordMismatch && (
                  <p className="mt-1 text-xs text-red-500">Passwords do not match</p>
                )}
              </FormField>
            </div>

            <div className="flex gap-2 pt-2">
              <button
                onClick={() => updateMutation.mutate()}
                disabled={!canSave}
                className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
              >
                {updateMutation.isPending ? 'Saving…' : 'Save Changes'}
              </button>
              <button
                onClick={() => setEditUser(null)}
                className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
              >
                Cancel
              </button>
            </div>
          </div>
        )}
      </DrawerPanel>

      {/* ── Create drawer ────────────────────────────────────────────────── */}
      <DrawerPanel open={showCreate} onClose={() => { setShowCreate(false); setCreateForm(emptyCreate) }} title="Add User" size="md">
        <div className="space-y-4 p-4">
          <FormField label="Full Name" required id="user-name">
            <input id="user-name" value={createForm.name}
              onChange={(e) => setCreateForm((f) => ({ ...f, name: e.target.value }))}
              className={inputClass} />
          </FormField>

          <FormField label="Email" required id="user-email">
            <input id="user-email" type="email" value={createForm.email}
              onChange={(e) => setCreateForm((f) => ({ ...f, email: e.target.value }))}
              className={inputClass} />
          </FormField>

          <FormField label="Phone" id="user-phone">
            <input id="user-phone" type="tel" value={createForm.phone}
              onChange={(e) => setCreateForm((f) => ({ ...f, phone: e.target.value }))}
              className={inputClass} />
          </FormField>

          <FormField label="Password" required id="user-password">
            <input id="user-password" type="password" value={createForm.password}
              placeholder="Min. 8 characters"
              onChange={(e) => setCreateForm((f) => ({ ...f, password: e.target.value }))}
              className={inputClass} />
          </FormField>

          <FormField label="Confirm Password" required id="user-confirm-password">
            <input id="user-confirm-password" type="password" value={createForm.confirmPassword}
              placeholder="Re-enter password"
              onChange={(e) => setCreateForm((f) => ({ ...f, confirmPassword: e.target.value }))}
              className={`w-full h-9 px-3 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] ${
                createPasswordMismatch ? 'border-red-400' : 'border-[var(--color-border)]'
              }`} />
            {createPasswordMismatch && (
              <p className="mt-1 text-xs text-red-500">Passwords do not match</p>
            )}
          </FormField>

          <FormField label="Role" id="user-role">
            <select id="user-role" value={createForm.role}
              onChange={(e) => setCreateForm((f) => ({ ...f, role: e.target.value }))}
              className={inputClass}>
              {ROLES.map((r) => <option key={r}>{r}</option>)}
            </select>
          </FormField>

          <FormField label="Branch" required id="user-branch">
            <select id="user-branch" value={createForm.branch_id || ''}
              onChange={(e) => setCreateForm((f) => ({ ...f, branch_id: Number(e.target.value) }))}
              className={inputClass}>
              <option value="" disabled>Select branch…</option>
              {activeBranches.map((b) => (
                <option key={b.id} value={b.id}>{b.name} ({b.code})</option>
              ))}
            </select>
            {!createForm.branch_id && (
              <p className="mt-1 text-xs text-red-500">Branch is required</p>
            )}
          </FormField>

          <div className="flex gap-2">
            <button
              onClick={() => {
                const payload = { ...createForm }
                delete (payload as Partial<typeof createForm>).confirmPassword
                createMutation.mutate(payload)
              }}
              disabled={
                !createForm.name ||
                !createForm.email ||
                createForm.password.length < 8 ||
                createForm.password !== createForm.confirmPassword ||
                !createForm.branch_id ||
                createMutation.isPending
              }
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {createMutation.isPending ? 'Creating…' : 'Create User'}
            </button>
            <button
              onClick={() => { setShowCreate(false); setCreateForm(emptyCreate) }}
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
