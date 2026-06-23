'use client'

import { Plus, Shirt } from 'lucide-react'
import { SubOrderCard } from '../SubOrderCard'
import { useWizard } from '../WizardContext'

export function StepSubOrders() {
  const {
    customer,
    memberLabel,
    subOrders,
    locked,
    progressDone,
    progressTotal,
    addSubOrder,
    duplicateSubOrder,
    removeSubOrder,
    patchSubOrder,
  } = useWizard()

  if (!customer) {
    return (
      <div className="rounded-xl border border-[var(--color-border)] bg-white p-6 text-center text-sm text-[var(--color-text-muted)]">
        Select a customer first.
      </div>
    )
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center gap-2 text-sm">
          <Shirt size={16} strokeWidth={1.75} className="text-[var(--color-brand)]" />
          <span className="font-semibold text-[var(--color-text-primary)]">
            {progressTotal} shirt{progressTotal !== 1 ? 's' : ''}
          </span>
          <span className="text-[var(--color-text-muted)]">·</span>
          <span className="text-[var(--color-text-secondary)]">{progressDone}/{progressTotal} complete</span>
        </div>
        <button
          type="button"
          onClick={addSubOrder}
          disabled={locked}
          className="inline-flex items-center gap-1.5 rounded-lg bg-[var(--color-brand)] px-3 h-9 text-sm font-medium text-white hover:bg-[var(--color-brand-dark)] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <Plus size={15} strokeWidth={2} /> Add Sub-Order
        </button>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">
        {subOrders.map((sub, i) => (
          <SubOrderCard
            key={sub.tempId}
            index={i}
            sub={sub}
            memberLabel={memberLabel}
            customerId={customer.id}
            canRemove={subOrders.length > 1}
            locked={locked}
            onPatch={patchSubOrder}
            onDuplicate={duplicateSubOrder}
            onRemove={removeSubOrder}
          />
        ))}
      </div>
    </div>
  )
}
