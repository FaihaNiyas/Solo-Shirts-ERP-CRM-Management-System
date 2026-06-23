'use client'

import { useMemo, useState } from 'react'
import { toast } from 'sonner'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import type { ColumnDef } from '@tanstack/react-table'
import { useSuppliers, useCreateSupplier, useUpdateSupplier } from '@/lib/api/hooks/useInventory'
import { PageHeader } from '@/components/ui/page-header'
import { DataTable } from '@/components/ui/data-table'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { TableSkeleton } from '@/components/ui/loading-skeleton'
import { usePermission } from '@/lib/auth/permissions'
import type { Supplier } from '@/lib/api/schemas/inventory'

const inputCls = 'w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]'

const Schema = z.object({
  code: z.string().min(1, 'Code required'),
  name: z.string().min(1, 'Name required'),
  gstin: z.string().optional(),
  phone: z.string().optional(),
  email: z.string().email().optional().or(z.literal('')),
  address: z.string().optional(),
  payment_terms: z.string().optional(),
  is_active: z.boolean().optional(),
})
type Form = z.infer<typeof Schema>

export default function SuppliersPage() {
  const { can } = usePermission()
  const canManage = can('inventory.suppliers.manage')
  const { data, isLoading } = useSuppliers()
  const [editTarget, setEditTarget] = useState<Supplier | null>(null)
  const [showCreate, setShowCreate] = useState(false)

  const createMutation = useCreateSupplier()
  const updateMutation = useUpdateSupplier(editTarget?.id ?? 0)

  const { register, handleSubmit, formState: { errors }, reset, setValue: setVal } = useForm<Form>({ resolver: zodResolver(Schema) })

  const suppliers = data?.data ?? []

  const columns: ColumnDef<Supplier, unknown>[] = useMemo(() => [
    { accessorKey: 'code', header: 'Code', cell: ({ row }) => <span className="font-mono text-xs">{row.original.code ?? '—'}</span> },
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'phone', header: 'Phone', cell: ({ row }) => <span className="text-sm">{row.original.phone ?? '—'}</span> },
    { accessorKey: 'payment_terms', header: 'Terms', cell: ({ row }) => <span className="text-sm">{row.original.payment_terms ?? '—'}</span> },
    {
      id: 'active',
      header: 'Status',
      cell: ({ row }) => (
        <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${row.original.is_active === false ? 'bg-gray-100 text-gray-500' : 'bg-green-50 text-green-700'}`}>
          {row.original.is_active === false ? 'Inactive' : 'Active'}
        </span>
      ),
    },
    ...(canManage ? [{
      id: 'actions',
      header: '',
      cell: ({ row }: { row: { original: Supplier } }) => (
        <button
          onClick={() => {
            const s = row.original
            setEditTarget(s)
            setVal('code', s.code ?? '')
            setVal('name', s.name ?? '')
            setVal('gstin', s.gstin ?? '')
            setVal('phone', s.phone ?? '')
            setVal('email', s.email ?? '')
            setVal('address', s.address ?? '')
            setVal('payment_terms', s.payment_terms ?? '')
            setVal('is_active', s.is_active !== false)
          }}
          className="text-xs text-[var(--color-brand)] hover:underline"
        >
          Edit
        </button>
      ),
    }] : []),
  ], [canManage, setVal])

  async function onSubmit(form: Form) {
    try {
      if (editTarget) {
        // code is immutable on update (not in the update request).
        const { code: _code, ...rest } = form
        void _code
        await updateMutation.mutateAsync(rest)
        toast.success('Supplier updated')
        setEditTarget(null)
      } else {
        await createMutation.mutateAsync(form)
        toast.success('Supplier created')
        setShowCreate(false)
      }
      reset()
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Suppliers"
        actions={canManage && (
          <button onClick={() => { reset({ is_active: true }); setShowCreate(true) }} className="px-4 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-xl hover:bg-[var(--color-brand-dark)] transition-colors">
            Add Supplier
          </button>
        )}
      />

      {isLoading ? <TableSkeleton rows={5} cols={5} /> : <DataTable data={suppliers} columns={columns} />}

      <DrawerPanel
        open={showCreate || editTarget !== null}
        onClose={() => { setShowCreate(false); setEditTarget(null); reset() }}
        title={editTarget ? 'Edit Supplier' : 'New Supplier'}
        size="md"
      >
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 p-4">
          <div className="grid grid-cols-2 gap-3">
            <FormField label="Code" required error={errors.code?.message}>
              <input {...register('code')} disabled={editTarget !== null} className={`${inputCls} disabled:bg-[var(--color-surface-alt)] disabled:text-[var(--color-text-muted)]`} />
            </FormField>
            <FormField label="GSTIN" error={errors.gstin?.message}>
              <input {...register('gstin')} className={inputCls} />
            </FormField>
          </div>
          <FormField label="Name" required error={errors.name?.message}>
            <input {...register('name')} className={inputCls} />
          </FormField>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="Phone" error={errors.phone?.message}>
              <input {...register('phone')} className={inputCls} />
            </FormField>
            <FormField label="Email" error={errors.email?.message}>
              <input {...register('email')} type="email" className={inputCls} />
            </FormField>
          </div>
          <FormField label="Payment terms" error={errors.payment_terms?.message}>
            <input {...register('payment_terms')} placeholder="e.g. Net 30" className={inputCls} />
          </FormField>
          <FormField label="Address">
            <textarea {...register('address')} rows={2} className="w-full px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] resize-none" />
          </FormField>
          <label className="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
            <input type="checkbox" {...register('is_active')} className="h-4 w-4 rounded border-[var(--color-border-mid)] text-[var(--color-brand)]" /> Active
          </label>
          <div className="flex gap-2 pt-2">
            <button type="submit" disabled={createMutation.isPending || updateMutation.isPending} className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors">
              {editTarget ? 'Update' : 'Create'}
            </button>
            <button type="button" onClick={() => { setShowCreate(false); setEditTarget(null); reset() }} className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors">
              Cancel
            </button>
          </div>
        </form>
      </DrawerPanel>
    </div>
  )
}
