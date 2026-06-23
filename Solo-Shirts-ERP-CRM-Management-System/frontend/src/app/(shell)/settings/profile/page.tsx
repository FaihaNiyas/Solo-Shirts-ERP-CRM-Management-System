'use client'

import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { toast } from 'sonner'
import { useMutation } from '@tanstack/react-query'
import { apiPut, apiPost, generateIdempotencyKey } from '@/lib/api/client'
import { ENDPOINTS } from '@/lib/api/endpoints'
import { useAuthStore } from '@/lib/auth/store'
import { PageHeader } from '@/components/ui/page-header'
import { FormField } from '@/components/ui/form-field'

const ProfileSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  email: z.string().email('Invalid email'),
})
type ProfileForm = z.infer<typeof ProfileSchema>

const PasswordSchema = z.object({
  current_password: z.string().min(1, 'Current password required'),
  new_password: z.string().min(8, 'At least 8 characters'),
  confirm_password: z.string().min(1, 'Confirm your new password'),
}).refine((d) => d.new_password === d.confirm_password, {
  message: 'Passwords do not match',
  path: ['confirm_password'],
})
type PasswordForm = z.infer<typeof PasswordSchema>

export default function ProfilePage() {
  const user = useAuthStore((s) => s.user)
  const setUser = useAuthStore((s) => s.setUser)
  const [pwOpen, setPwOpen] = useState(false)

  const { register: regProfile, handleSubmit: hsProfile, formState: { errors: pErr, isDirty: pDirty } } =
    useForm<ProfileForm>({
      resolver: zodResolver(ProfileSchema),
      defaultValues: { name: user?.name ?? '', email: user?.email ?? '' },
    })

  const { register: regPw, handleSubmit: hsPw, formState: { errors: pwErr }, reset: resetPw } =
    useForm<PasswordForm>({ resolver: zodResolver(PasswordSchema) })

  const profileMutation = useMutation({
    mutationFn: (data: ProfileForm) =>
      apiPut<{ name: string; email: string }>(ENDPOINTS.auth.updateProfile, data, {
        headers: { 'Idempotency-Key': generateIdempotencyKey() },
      }),
    onSuccess: (res) => {
      if (user) setUser({ ...user, name: res.data.name, email: res.data.email })
      toast.success('Profile updated')
    },
    onError: (err: unknown) => {
      toast.error((err as { message?: string })?.message ?? 'Failed to update profile')
    },
  })

  const passwordMutation = useMutation({
    mutationFn: (data: PasswordForm) =>
      apiPost(ENDPOINTS.auth.changePassword, {
        current_password: data.current_password,
        new_password: data.new_password,
      }, { headers: { 'Idempotency-Key': generateIdempotencyKey() } }),
    onSuccess: () => {
      toast.success('Password changed')
      setPwOpen(false)
      resetPw()
    },
    onError: (err: unknown) => {
      toast.error((err as { message?: string })?.message ?? 'Incorrect current password')
    },
  })

  return (
    <div className="space-y-8 max-w-2xl">
      <PageHeader title="Profile" subtitle="Your account details" />

      {/* FE-003/FE-004: the backend exposes no PUT /auth/me or /auth/change-password
          route yet, so these write actions are disabled until confirmed. */}
      <div className="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800">
        Profile update &amp; password change are temporarily disabled —{' '}
        <strong>Backend gap, needs confirmation</strong> (no <code>PUT /auth/me</code> or{' '}
        <code>/auth/change-password</code> route on the backend yet).
      </div>

      {/* Role chip */}
      <div className="flex items-center gap-2">
        {(user?.roles ?? []).map((r) => (
          <span
            key={r}
            className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-[var(--color-brand-light)] text-[var(--color-brand-dark)]"
          >
            {r}
          </span>
        ))}
      </div>

      {/* Profile form — submission disabled (FE-004: backend has no PUT /auth/me) */}
      <form
        onSubmit={(e) => e.preventDefault()}
        className="space-y-4"
      >
        <FormField label="Full Name" required error={pErr.name?.message}>
          <input
            {...regProfile('name')}
            className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
          />
        </FormField>

        <FormField label="Email" required error={pErr.email?.message}>
          <input
            {...regProfile('email')}
            type="email"
            className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
          />
        </FormField>

        <button
          type="submit"
          disabled
          title="Backend gap — needs confirmation"
          className="px-4 py-2 text-sm bg-[var(--color-brand)] text-white font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          Save Changes
        </button>
      </form>

      <div className="border-t border-[var(--color-border)] pt-6">
        {!pwOpen ? (
          <button
            onClick={() => setPwOpen(true)}
            className="text-sm text-[var(--color-brand)] hover:underline"
          >
            Change password
          </button>
        ) : (
          <form
            onSubmit={(e) => e.preventDefault()}
            className="space-y-4"
          >
            <p className="text-sm font-semibold text-[var(--color-text-primary)]">Change Password</p>

            <FormField label="Current Password" required error={pwErr.current_password?.message}>
              <input
                {...regPw('current_password')}
                type="password"
                autoComplete="current-password"
                className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              />
            </FormField>

            <FormField label="New Password" required error={pwErr.new_password?.message}>
              <input
                {...regPw('new_password')}
                type="password"
                autoComplete="new-password"
                className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              />
            </FormField>

            <FormField label="Confirm New Password" required error={pwErr.confirm_password?.message}>
              <input
                {...regPw('confirm_password')}
                type="password"
                autoComplete="new-password"
                className="w-full h-9 px-3 text-sm border border-[var(--color-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)]"
              />
            </FormField>

            <div className="flex gap-2">
              <button
                type="submit"
                disabled
                title="Backend gap — needs confirmation"
                className="px-4 py-2 text-sm bg-[var(--color-brand)] text-white font-medium rounded-lg hover:bg-[var(--color-brand-dark)] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Change Password
              </button>
              <button
                type="button"
                onClick={() => { setPwOpen(false); resetPw() }}
                className="px-4 py-2 text-sm border border-[var(--color-border)] rounded-lg text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  )
}
