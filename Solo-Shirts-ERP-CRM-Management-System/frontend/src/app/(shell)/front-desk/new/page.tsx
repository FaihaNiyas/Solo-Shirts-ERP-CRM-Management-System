'use client'

import { Suspense } from 'react'
import { useSearchParams } from 'next/navigation'
import { FrontDeskWizardProvider } from '@/components/front-desk/wizard/WizardContext'
import { FrontDeskWizard } from '@/components/front-desk/wizard/FrontDeskWizard'

function NewOrderWizard() {
  const params = useSearchParams()
  // Resume ONLY when an explicit draft id is present (?draft= or ?draft_id=).
  // Any other entry to /front-desk/new (incl. ?new=1) opens a blank wizard.
  const raw = params.get('draft') ?? params.get('draft_id')
  const resumeDraftId = raw && /^\d+$/.test(raw) ? Number(raw) : null

  // A distinct key per draft (or "new") forces a clean provider remount when the
  // counter switches between drafts, so state never bleeds across them.
  return (
    <FrontDeskWizardProvider key={resumeDraftId ?? 'new'} resumeDraftId={resumeDraftId}>
      <FrontDeskWizard />
    </FrontDeskWizardProvider>
  )
}

export default function FrontDeskNewOrderPage() {
  return (
    <Suspense fallback={null}>
      <NewOrderWizard />
    </Suspense>
  )
}
