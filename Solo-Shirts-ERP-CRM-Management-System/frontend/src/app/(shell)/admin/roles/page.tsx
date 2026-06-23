'use client'

import { useMemo, useState } from 'react'
import { toast } from 'sonner'
import { Shield, Lock, Pencil, Trash2 } from 'lucide-react'
import { PageHeader } from '@/components/ui/page-header'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { usePermission } from '@/lib/auth/permissions'
import {
  useRoles, useCreateRole, useUpdateRole, useDeleteRole,
  usePermissionsList, type Role,
} from '@/lib/api/hooks/useRbac'

function errMsg(err: unknown): string {
  return (err as { message?: string })?.message ?? 'Something went wrong'
}

export default function AdminRolesPage() {
  const { can } = usePermission()
  const isAllowed = can('roles.view')

  const { data: roles = [], isLoading } = useRoles()
  const { data: permissions = [] } = usePermissionsList()

  const [editing, setEditing] = useState<Role | null>(null)
  const [creating, setCreating] = useState(false)
  const [name, setName] = useState('')
  const [selected, setSelected] = useState<Set<string>>(new Set())

  const createRole = useCreateRole()
  const updateRole = useUpdateRole(editing?.id ?? 0)
  const deleteRole = useDeleteRole()

  // Permissions grouped by module (orders.*, customers.*, …) for the checkboxes.
  const grouped = useMemo(() => {
    const map: Record<string, string[]> = {}
    for (const p of permissions) (map[p.group] ??= []).push(p.name)
    return Object.entries(map).sort(([a], [b]) => a.localeCompare(b))
  }, [permissions])

  function openCreate() {
    setEditing(null); setCreating(true); setName(''); setSelected(new Set())
  }
  function openEdit(role: Role) {
    setCreating(false); setEditing(role); setName(role.name); setSelected(new Set(role.permissions))
  }
  function close() { setEditing(null); setCreating(false) }

  function toggle(perm: string) {
    setSelected((s) => {
      const next = new Set(s)
      next.has(perm) ? next.delete(perm) : next.add(perm)
      return next
    })
  }

  function save() {
    const permissions = Array.from(selected)
    if (creating) {
      createRole.mutate(
        { name: name.trim(), permissions },
        { onSuccess: () => { toast.success('Role created'); close() }, onError: (e) => toast.error(errMsg(e)) },
      )
    } else if (editing) {
      const body = editing.is_system ? { permissions } : { name: name.trim(), permissions }
      updateRole.mutate(body, {
        onSuccess: () => { toast.success('Role updated'); close() },
        onError: (e) => toast.error(errMsg(e)),
      })
    }
  }

  function remove(role: Role) {
    if (!confirm(`Delete role "${role.name}"? This cannot be undone.`)) return
    deleteRole.mutate(role.id, {
      onSuccess: () => toast.success('Role deleted'),
      onError: (e) => toast.error(errMsg(e)),
    })
  }

  if (!isAllowed) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center">
        <Shield size={40} strokeWidth={1.25} className="text-[var(--color-text-muted)]" />
        <p className="text-sm text-[var(--color-text-muted)]">Owner or Admin access required</p>
      </div>
    )
  }

  const saving = createRole.isPending || updateRole.isPending
  const drawerOpen = creating || editing !== null

  return (
    <div className="space-y-6">
      <PageHeader
        title="Roles"
        description="Create roles and choose which permissions each one grants."
        breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Roles' }]}
        actions={
          <button
            onClick={openCreate}
            className="px-4 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-xl hover:bg-[var(--color-brand-dark)] transition-colors"
          >
            Add Role
          </button>
        }
      />

      {isLoading && <TableSkeleton rows={8} cols={4} />}

      {!isLoading && (
        <div className="rounded-xl border border-[var(--color-border)] overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-[var(--color-surface-alt)]">
              <tr>
                {['Role', 'Permissions', 'Users', ''].map((h) => (
                  <th key={h} className="px-4 py-2 text-left text-xs font-semibold text-[var(--color-text-muted)]">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-[var(--color-border)]">
              {roles.map((r) => (
                <tr key={r.id} className="hover:bg-[var(--color-surface-alt)] transition-colors">
                  <td className="px-4 py-3 font-medium">
                    <span className="flex items-center gap-2">
                      {r.name}
                      {r.is_system && (
                        <span className="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium bg-[var(--color-surface-alt)] text-[var(--color-text-muted)] rounded-full">
                          <Lock size={10} /> System
                        </span>
                      )}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-[var(--color-text-muted)]">{r.permissions.length}</td>
                  <td className="px-4 py-3 text-[var(--color-text-muted)]">{r.users_count}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-1">
                      <button
                        onClick={() => openEdit(r)}
                        title="Edit"
                        className="p-1.5 rounded-lg text-[var(--color-text-muted)] hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-brand)] transition-colors"
                      >
                        <Pencil size={15} />
                      </button>
                      {!r.is_system && (
                        <button
                          onClick={() => remove(r)}
                          title="Delete"
                          className="p-1.5 rounded-lg text-[var(--color-text-muted)] hover:bg-[var(--bg-danger)] hover:text-[var(--color-danger)] transition-colors"
                        >
                          <Trash2 size={15} />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <DrawerPanel open={drawerOpen} onClose={close} title={creating ? 'Add Role' : `Edit ${editing?.name ?? ''}`} size="lg">
        <div className="space-y-5 p-4">
          <FormField label="Role name" required id="role-name">
            <input
              id="role-name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              disabled={editing?.is_system}
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] disabled:bg-[var(--color-surface-alt)] disabled:text-[var(--color-text-muted)]"
            />
            {editing?.is_system && (
              <p className="mt-1 text-xs text-[var(--color-text-muted)]">System role — name is fixed, but you can edit its permissions.</p>
            )}
          </FormField>

          <div>
            <p className="mb-2 text-sm font-semibold text-[var(--color-text-primary)]">
              Permissions <span className="text-[var(--color-text-muted)] font-normal">({selected.size} selected)</span>
            </p>
            <div className="space-y-4 max-h-[55vh] overflow-y-auto pr-1">
              {grouped.map(([group, perms]) => (
                <div key={group}>
                  <p className="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">{group}</p>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                    {perms.map((perm) => (
                      <label key={perm} className="flex items-center gap-2 text-[13px] cursor-pointer text-[var(--color-text-secondary)]">
                        <input
                          type="checkbox"
                          checked={selected.has(perm)}
                          onChange={() => toggle(perm)}
                          className="rounded border-[var(--color-border)] text-[var(--color-brand)] focus:ring-[var(--color-brand)]"
                        />
                        <span className="font-mono text-[12px]">{perm}</span>
                      </label>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="flex gap-2 pt-2">
            <button
              onClick={save}
              disabled={saving || (creating && !name.trim())}
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {saving ? 'Saving…' : creating ? 'Create Role' : 'Save Changes'}
            </button>
            <button
              onClick={close}
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
