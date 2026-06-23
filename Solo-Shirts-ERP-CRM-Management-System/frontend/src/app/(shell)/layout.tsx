import { AppShell } from '@/components/shell/AppShell'
import { AuthGuard } from '@/components/shell/AuthGuard'
import { BranchProvider } from '@/components/providers/BranchProvider'
import { MotionPreferenceProvider } from '@/components/providers/MotionPreferenceProvider'
import { ErrorDrawer } from '@/components/shell/ErrorDrawer'
import { PreferencesProvider } from '@/lib/preferences/PreferencesContext'
import { KeyboardShortcutProvider } from '@/components/shortcuts/KeyboardShortcutProvider'
import { OfflineBanner } from '@/components/ui/offline-banner'

export default function ShellLayout({ children }: { children: React.ReactNode }) {
  return (
    <PreferencesProvider>
      <MotionPreferenceProvider>
        <BranchProvider>
          <AuthGuard>
            <KeyboardShortcutProvider>
              <OfflineBanner />
              <AppShell>
                {children}
              </AppShell>
              <ErrorDrawer />
            </KeyboardShortcutProvider>
          </AuthGuard>
        </BranchProvider>
      </MotionPreferenceProvider>
    </PreferencesProvider>
  )
}
