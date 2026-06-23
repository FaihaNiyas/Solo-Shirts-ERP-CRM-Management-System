import { AccessDenied } from '@/components/shell/AccessDenied'

// FE-011 — a reachable /forbidden route (redirect target for permission-denied).
export default function ForbiddenPage() {
  return <AccessDenied />
}
