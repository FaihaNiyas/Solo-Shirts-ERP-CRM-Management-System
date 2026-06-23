'use client'

import { useState } from 'react'
import { Plus } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { toast } from 'sonner'
import { useFamilyMembers, useCreateFamilyMember } from '@/lib/api/hooks/useCustomers'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { cn } from '@/lib/utils'
import type { Customer } from '@/lib/api/schemas/customers'

const Schema = z.object({
  name: z.string().min(1, 'Name required'),
  relation: z.string().optional(),
  gender: z.string().optional(),
})
type Form = z.infer<typeof Schema>

interface FamilyMemberSwitcherProps {
  customer: Customer
  /** null = Self (the primary customer). */
  selectedMemberId: number | null
  onSelect: (memberId: number | null, label: string) => void
}

export function FamilyMemberSwitcher({ customer, selectedMemberId, onSelect }: FamilyMemberSwitcherProps) {
  const [showAdd, setShowAdd] = useState(false)

  const customerId = customer.id
  const { data: members = [] } = useFamilyMembers(customerId)
  const addMember = useCreateFamilyMember(customerId)
  const { register, handleSubmit, formState: { errors }, reset } = useForm<Form>({ resolver: zodResolver(Schema) })

  async function onSubmit(data: Form) {
    try {
      await addMember.mutateAsync(data as Parameters<typeof addMember.mutateAsync>[0])
      setShowAdd(false)
      reset()
      toast.success('Family member added')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed')
    }
  }

  const tabs = [
    { id: null as number | null, label: `${customer.name.split(' ')[0]} (Self)` },
    ...members.map((m) => ({ id: m.id as number | null, label: m.name.split(' ')[0] })),
  ]

  return (
    <>
      <div className="flex items-center gap-1.5 overflow-x-auto pb-1">
        {tabs.map(({ id, label }) => (
          <button
            key={id ?? 'main'}
            onClick={() => onSelect(id, label)}
            className={cn(
              'shrink-0 px-3 py-1.5 text-sm font-medium rounded-full transition-colors',
              selectedMemberId === id
                ? 'bg-[var(--color-brand)] text-white'
                : 'bg-[var(--color-surface-alt)] text-[var(--color-text-secondary)] hover:bg-[var(--color-border)]',
            )}
          >
            {label}
          </button>
        ))}
        <button
          onClick={() => setShowAdd(true)}
          className="shrink-0 flex items-center gap-1 px-3 py-1.5 text-sm text-[var(--color-brand)] border border-dashed border-[var(--color-brand)] rounded-full hover:bg-[var(--color-brand-light)] transition-colors"
        >
          <Plus size={12} strokeWidth={2} /> Add
        </button>
      </div>

      <DrawerPanel open={showAdd} onClose={() => { setShowAdd(false); reset() }} title="Add Family Member" size="sm">
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 p-4">
          <FormField label="Name" required error={errors.name?.message}>
            <input {...register('name')} className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]" />
          </FormField>
          <FormField label="Relation">
            <input {...register('relation')} className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]" placeholder="Spouse, Son…" />
          </FormField>
          <FormField label="Gender">
            <select {...register('gender')} className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] bg-white">
              <option value="">Select</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </FormField>
          <div className="flex gap-2 pt-2">
            <button type="submit" disabled={addMember.isPending} className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors">
              {addMember.isPending ? 'Adding…' : 'Add Member'}
            </button>
            <button type="button" onClick={() => { setShowAdd(false); reset() }} className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors">
              Cancel
            </button>
          </div>
        </form>
      </DrawerPanel>
    </>
  )
}
