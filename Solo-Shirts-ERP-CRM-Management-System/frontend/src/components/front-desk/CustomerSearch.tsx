'use client'

import { useState } from 'react'
import { Plus, Clock } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { useQuery } from '@tanstack/react-query'
import { useCustomers, useCreateCustomer } from '@/lib/api/hooks/useCustomers'
import { useAuthStore } from '@/lib/auth/store'
import { apiGet } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { SearchInput } from '@/components/ui/search-input'
import { DrawerPanel } from '@/components/ui/drawer-panel'
import { FormField } from '@/components/ui/form-field'
import { EmptyState } from '@/components/ui/empty-state'
import type { Customer } from '@/lib/api/schemas/customers'

interface BranchRef { id: number; name: string; code: string }

const CreateSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  phone: z.string().min(10, 'Valid phone required'),
  email: z.string().email().optional().or(z.literal('')),
  address: z.string().optional(),
  branch_id: z.number().optional(),
})
type CreateForm = z.infer<typeof CreateSchema>

function getRecent(userId: number): Customer[] {
  try {
    return JSON.parse(localStorage.getItem(`recent_customers:${userId}`) ?? '[]') as Customer[]
  } catch { return [] }
}

function saveRecent(userId: number, c: Customer) {
  try {
    const list = getRecent(userId).filter((x) => x.id !== c.id)
    localStorage.setItem(`recent_customers:${userId}`, JSON.stringify([c, ...list].slice(0, 5)))
  } catch { /* ignore */ }
}

interface CustomerSearchProps {
  /** Called when a customer is picked (from search, recent, or after create). */
  onSelect: (customer: Customer) => void
}

export function CustomerSearch({ onSelect }: CustomerSearchProps) {
  const user = useAuthStore((s) => s.user)
  const activeBranch = useAuthStore((s) => s.activeBranch)
  const [query, setQuery] = useState('')
  const [showCreate, setShowCreate] = useState(false)

  const { data: results, isLoading } = useCustomers({ search: query || undefined, per_page: 10 })
  const createMutation = useCreateCustomer()

  // Resolve the branch the customer should be created under. The backend's
  // BranchContext returns null for Owners even when they have a branch, so we
  // must always send branch_id explicitly.
  const resolvedBranchId = user?.branch?.id ?? user?.branch_id ?? activeBranch?.id ?? null

  // Only force a manual pick when no branch can be resolved at all.
  const needsBranchPicker = resolvedBranchId == null

  const { data: branches = [] } = useQuery<BranchRef[]>({
    queryKey: ['branches', 'active'],
    queryFn: () => apiGet<BranchRef[]>(ENDPOINTS.branchesActiveList).then((r) => r.data),
    enabled: showCreate && needsBranchPicker,
  })

  const recent = user ? getRecent(user.id) : []
  // The list endpoint returns a bare array; tolerate a paginated shape too.
  const searchResults: Customer[] = Array.isArray(results)
    ? (results as Customer[])
    : ((results as { data?: Customer[] } | undefined)?.data ?? [])
  const list: Customer[] = query.trim() ? searchResults : recent

  const { register, handleSubmit, formState: { errors }, reset, setValue, watch } = useForm<CreateForm>({
    resolver: zodResolver(CreateSchema),
  })

  function select(c: Customer) {
    onSelect(c)
    if (user) saveRecent(user.id, c)
  }

  async function onSubmit(data: CreateForm) {
    const branchId = data.branch_id ?? resolvedBranchId ?? undefined
    if (!branchId) {
      toast.error('Please select a branch')
      return
    }
    try {
      const res = await createMutation.mutateAsync({ ...data, branch_id: branchId })
      select(res.data)
      setShowCreate(false)
      reset()
      toast.success('Customer created')
    } catch (err: unknown) {
      toast.error((err as { message?: string })?.message ?? 'Failed to create customer')
    }
  }

  return (
    <div className="space-y-2">
      <SearchInput
        value={query}
        onChange={setQuery}
        placeholder="Name or phone…"
        loading={isLoading}
      />

      {!query && recent.length > 0 && (
        <p className="flex items-center gap-1 text-xs text-[var(--color-text-muted)] px-1">
          <Clock size={11} strokeWidth={1.75} /> Recent
        </p>
      )}

      <div className="space-y-1">
        {list.map((c) => (
          <button
            key={c.id}
            onClick={() => select(c)}
            className="w-full flex items-center gap-3 p-3 rounded-lg border border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface-alt)] transition-colors text-left"
          >
            <div className="w-8 h-8 rounded-full bg-[var(--color-brand-light)] flex items-center justify-center shrink-0">
              <span className="text-xs font-semibold text-[var(--color-brand)]">
                {c.name.charAt(0).toUpperCase()}
              </span>
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-[var(--color-text-primary)] truncate">{c.name}</p>
              <p className="text-xs text-[var(--color-text-muted)]">
                {c.phone ?? (c.phone_last4 ? `••••${c.phone_last4}` : '—')}
                {c.last_order_date ? ` · ${c.last_order_date}` : ''}
              </p>
            </div>
          </button>
        ))}

        {query && !isLoading && list.length === 0 && (
          <EmptyState
            title="No customers found"
            description={`No results for "${query}"`}
            action={
              <button
                onClick={() => setShowCreate(true)}
                className="text-sm text-[var(--color-brand)] underline hover:no-underline"
              >
                + New Customer
              </button>
            }
          />
        )}
      </div>

      {!query && (
        <button
          onClick={() => setShowCreate(true)}
          className="w-full flex items-center justify-center gap-2 py-2.5 text-sm text-[var(--color-brand)] border border-dashed border-[var(--color-brand)] rounded-lg hover:bg-[var(--color-brand-light)] transition-colors"
        >
          <Plus size={14} strokeWidth={2} /> New Customer
        </button>
      )}

      <DrawerPanel open={showCreate} onClose={() => { setShowCreate(false); reset() }} title="New Customer" size="md">
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 p-4">
          <FormField label="Full Name" required error={errors.name?.message}>
            <input
              {...register('name')}
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              placeholder="Customer name"
            />
          </FormField>
          <FormField label="Phone" required error={errors.phone?.message}>
            <input
              {...register('phone')}
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              placeholder="+91 98765 43210"
            />
          </FormField>
          <FormField label="Email" error={errors.email?.message}>
            <input
              {...register('email')}
              type="email"
              className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
            />
          </FormField>
          <FormField label="Address">
            <textarea
              {...register('address')}
              rows={2}
              className="w-full px-3 py-2 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] resize-none"
            />
          </FormField>

          {needsBranchPicker && (
            <FormField label="Branch" required error={errors.branch_id?.message}>
              <select
                value={watch('branch_id') ?? ''}
                onChange={(e) => setValue('branch_id', e.target.value ? Number(e.target.value) : undefined)}
                className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] bg-white"
              >
                <option value="" disabled>Select branch…</option>
                {branches.map((b) => (
                  <option key={b.id} value={b.id}>{b.name} ({b.code})</option>
                ))}
              </select>
            </FormField>
          )}

          <div className="flex gap-2 pt-2">
            <button
              type="submit"
              disabled={createMutation.isPending}
              className="flex-1 py-2 bg-[var(--color-brand)] text-white text-sm font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 transition-colors"
            >
              {createMutation.isPending ? 'Creating…' : 'Create Customer'}
            </button>
            <button
              type="button"
              onClick={() => { setShowCreate(false); reset() }}
              className="px-4 py-2 border border-[var(--color-border)] text-sm rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
            >
              Cancel
            </button>
          </div>
        </form>
      </DrawerPanel>
    </div>
  )
}
