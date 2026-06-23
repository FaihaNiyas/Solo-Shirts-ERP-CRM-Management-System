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
  usePermissionsList, useCreatePermission, useUpdatePermission, useDeletePermission,
  type Permission,
} from '@/lib/api/hooks/useRbac'

function errMsg(err: unknown): string {
  return (err as { message?: string })?.message ?? 'Something went wrong'
}

export default function AdminPermissionsPage() {
  const { can } = usePermission()
  const isAllowed = can('permissions.view')

  const { data: permissions = [], isLoading } = usePermissionsList()

  const [editing, setEditing] = useState<Permission | null>(null)
  const [creating, setCreating] = useState(false)
  const [name, setName] = useState('')

  const createPerm = useCreatePermission()
  const updatePerm = useUpdatePermission(editing?.id ?? 0)
  const deletePerm = useDeletePermission()

  const grouped = useMemo(() => {
    const map: Record<string, Permission[]> = {}
    for (const p of permissions) (map[p.group] ??= []).push(p)
    return Object.entries(map).sort(([a], [b]) => a.localeCompare(b))
  }, [permissions])

  function openCreate() { setEditing(null); setCreating(true); setName('') }
  function openEdit(p: Permission) { setCreating(false); setEditing(p); setName(p.name) }
  function close() { setEditing(null); setCreating(false) }

  function save() {
    if (creating) {
      createPerm.mutate({ name: name.trim() }, {
        onSuccess: () => { toast.success('Permission created'); close() },
        onError: (e) => toast.error(errMsg(e)),
      })
    } else if (editing) {
      updatePerm.mutate({ name: name.trim() }, {
        onSuccess: () => { toast.success('Permission updated'); close() },
        onError: (e) => toast.error(errMsg(e)),
      })
    }
  }

  function remove(p: Permission) {
    if (!confirm(`Delete permission "${p.name}"? It will be removed from all roles.`)) return
    deletePerm.mutate(p.id, {
      onSuccess: () => toast.success('Permission deleted'),
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

  const saving = createPerm.isPending || updatePerm.isPending
  const drawerOpen = creating || editing !== null

  return (
    <div className="space-y-6">
      <PageHeader
        title="Permissions"
        description="The permission catalogue. System permissions are fixed; you can add custom ones."
        breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Permissions' }]}
        actions={
          <button
            onClick={openCreate}
            className="px-4 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-xl hover:bg-[var(--color-brand-dark)] transition-colors"
          >
            Add Permission
          </button>
        }
      />

      {isLoading && <TableSkeleton rows={10} cols={3} />}

      {!isLoading && (
        <div className="space-y-5">
          {grouped.map(([group, perms]) => (
            <div key={group} className="rounded-xl border border-[var(--color-border)] overflow-hidden">
              <p className="px-4 py-2 bg-[var(--color-surface-alt)] text-[11px] font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">
                {group}
              </p>
              <ul className="divide-y divide-[var(--color-border)] bg-white">
                {perms.map((p) => (
                  <li key={p.id} className="flex items-center justify-between px-4 py-2.5 hover:bg-[var(--color-surface-alt)] transition-colors">
                    <span className="flex items-center gap-2">
                      <span className="font-mono text-[13px] text-[var(--color-text-primary)]">{p.name}</span>
                      {p.is_system && (
                        <span className="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium bg-[var(--color-surface-alt)] text-[var(--color-text-muted)] rounded-full">
                          <Lock size={10} /> System
                        </span>
                      )}
                      <span className="text-[11px] text-[var(--color-text-muted)]">{p.roles_count} role(s)</span>
                    </span>
                    {!p.is_system && (
                      <div className="flex items-center gap-1">
                        <button onClick={() => openEdit(p)} title="Edit"
                          className="p-1.5 rounded-lg text-[var(--color-text-muted)] hover:bg-[var(--color-surface-alt)] hover:text-[var(--color-brand)] transition-colors">
                          <Pencil size={15} />
                        </button>
                        <button onClick={() => remove(p)} title="Delete"
                          className="p-1.5 rounded-lg text-[var(--color-text-muted)] hover:bg-[var(--bg-danger)] hover:text-[var(--color-danger)] transition-colors">
                          <Trash2 size={15} />
                        </button>
                      </div>
                    )}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      )}

      <DrawerPanel open={drawerOpen} onClose={close} title={creating ? 'Add Permission' : 'Edit Permission'} size="md">
        <div className="space-y-4 p-4">
          <FormField label="Permission name" required id="perm-name">
            <input
              id="perm-name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="module.action  e.g. reports.export"
              className="w-full h-9 px-3 text-sm font-mono border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
            <p className="mt-1 text-xs text-[var(--color-text-muted)]">Lowercase, dot-separated: letters, numbers, underscore, dot.</p>
          </FormField>
          <div className="flex gap-2">
            <button
              onClick={save}
              disabled={saving || !name.trim()}
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {saving ? 'Saving…' : creating ? 'Create Permission' : 'Save Changes'}
            </button>
            <button onClick={close}
              className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors">
              Cancel
            </button>
          </div>
        </div>
      </DrawerPanel>
    </div>
  )
}
