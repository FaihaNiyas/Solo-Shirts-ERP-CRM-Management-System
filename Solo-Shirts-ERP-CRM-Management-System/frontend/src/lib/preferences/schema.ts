import { z } from 'zod'

export const PreferencesSchema = z.object({
  theme: z.enum(['light', 'dark', 'system']).default('light'),
  brandColor: z.string().default('#D97706'),
  sidebarStyle: z.enum(['compact', 'expanded']).default('expanded'),
  fontSize: z.enum(['small', 'default', 'large']).default('default'),
  dataDensity: z.enum(['compact', 'default', 'comfortable']).default('default'),
  borderRadius: z.enum(['soft', 'rounded', 'sharp']).default('rounded'),
  language: z.enum(['en', 'ta', 'hi', 'kn']).default('en'),
  dateFormat: z.enum(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD']).default('DD/MM/YYYY'),
  measurementUnit: z.enum(['cm', 'inches']).default('cm'),
  animationEnabled: z.boolean().default(true),
  reducedMotion: z.boolean().default(false),
  soundFeedback: z.boolean().default(false),
  autoSaveDraft: z.boolean().default(true),
  // Front Desk keyboard shortcuts (F1–F5, Ctrl+S, Ctrl+Enter). OFF by default for
  // a safe rollout — opt-in per browser/user via Settings → Preferences.
  keyboardShortcutsEnabled: z.boolean().default(false),
  notificationChannel: z.enum(['whatsapp', 'email', 'sms']).default('whatsapp'),
  frontDesk: z
    .object({
      defaultSearchMethod: z.enum(['name', 'phone']).default('name'),
      defaultGarmentType: z.string().default('Shirt'),
      defaultSleeveOption: z.enum(['full', 'half']).default('full'),
      defaultFitOption: z.enum(['slim', 'regular', 'loose']).default('regular'),
      showGarmentVisualizer: z.boolean().default(true),
      showMeasurementGuide: z.boolean().default(true),
    })
    .default({}),
  production: z
    .object({
      defaultView: z.enum(['kanban', 'list']).default('kanban'),
      showOnlyAssigned: z.boolean().default(false),
      autoRefreshInterval: z.number().default(30),
      highlightOverdue: z.boolean().default(true),
    })
    .default({}),
  inventory: z
    .object({
      showLowStockAlert: z.boolean().default(true),
      defaultRollView: z.enum(['list', 'grid']).default('list'),
      alwaysShowStockBreakdown: z.boolean().default(true),
    })
    .default({}),
  finance: z
    .object({
      currencyPlacement: z.enum(['prefix', 'suffix']).default('prefix'),
      showBalanceWarningBadges: z.boolean().default(true),
      confirmBeforePayment: z.boolean().default(true),
    })
    .default({}),
  dashboard: z
    .object({
      pinnedWidgets: z.array(z.string()).default([]),
      hiddenWidgets: z.array(z.string()).default([]),
      widgetOrder: z.array(z.string()).default([]),
      compactMode: z.boolean().default(false),
      defaultLanding: z.string().default('/dashboard'),
    })
    .default({}),
})

export type Preferences = z.infer<typeof PreferencesSchema>
export const defaultPreferences = PreferencesSchema.parse({})
