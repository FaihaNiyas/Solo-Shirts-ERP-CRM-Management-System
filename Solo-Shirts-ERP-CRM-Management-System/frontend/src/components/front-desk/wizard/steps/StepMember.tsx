'use client'

import { Users } from 'lucide-react'
import { FamilyMemberSwitcher } from '@/components/front-desk/FamilyMemberSwitcher'
import { SectionCard } from '@/components/ui/section-card'
import { useWizard } from '../WizardContext'

export function StepMember() {
  const { customer, memberId, memberLabel, setMember } = useWizard()

  if (!customer) {
    return (
      <SectionCard>
        <p className="py-6 text-center text-sm text-[var(--color-text-muted)]">
          Select a customer first.
        </p>
      </SectionCard>
    )
  }

  return (
    <SectionCard
      title="Who is this order for?"
      description="Pick Self or a family member. Each shirt's measurement is chosen later, per sub-order."
    >
      <FamilyMemberSwitcher customer={customer} selectedMemberId={memberId} onSelect={setMember} />

      <div className="mt-4 flex items-center gap-2 rounded-lg bg-[var(--color-surface-alt)] px-3 py-2.5 text-sm">
        <Users size={15} strokeWidth={1.75} className="text-[var(--color-brand)] shrink-0" />
        <span className="text-[var(--color-text-secondary)]">
          Order for: <span className="font-semibold text-[var(--color-text-primary)]">{memberLabel || customer.name.split(' ')[0]}</span>
        </span>
      </div>
    </SectionCard>
  )
}
