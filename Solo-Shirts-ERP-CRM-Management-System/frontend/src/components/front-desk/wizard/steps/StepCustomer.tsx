'use client'

import { Check, User } from 'lucide-react'
import { CustomerSearch } from '@/components/front-desk/CustomerSearch'
import { SectionCard } from '@/components/ui/section-card'
import { useWizard } from '../WizardContext'

export function StepCustomer() {
  const { customer, setCustomer } = useWizard()

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <SectionCard title="Search or create customer" description="By name or phone">
        <CustomerSearch onSelect={setCustomer} />
      </SectionCard>

      <SectionCard title="Selected customer">
        {customer ? (
          <div className="flex items-start gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--color-brand-light)] shrink-0">
              <span className="text-sm font-semibold text-[var(--color-brand)]">
                {customer.name.charAt(0).toUpperCase()}
              </span>
            </div>
            <div className="min-w-0 flex-1">
              <p className="text-sm font-semibold text-[var(--color-text-primary)] truncate">{customer.name}</p>
              {customer.customer_code && (
                <p className="ss-mono text-xs text-[var(--color-text-muted)]">{customer.customer_code}</p>
              )}
              <p className="text-xs text-[var(--color-text-muted)] mt-0.5">
                {customer.phone_masked ?? (customer.phone ? `****${customer.phone.slice(-4)}` : '—')}
              </p>
              <span className="mt-2 inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700">
                <Check size={11} strokeWidth={2.5} /> Selected
              </span>
            </div>
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center py-8 text-center">
            <User size={26} strokeWidth={1.5} className="text-[var(--color-text-muted)] mb-2" />
            <p className="text-sm text-[var(--color-text-muted)]">No customer selected yet.</p>
            <p className="text-xs text-[var(--color-text-muted)]">Search on the left, or create a new one.</p>
          </div>
        )}
      </SectionCard>
    </div>
  )
}
