'use client'

import Link from 'next/link'
import {
  UserPlus,
  FilePlus2,
  Search,
  PlayCircle,
  ShoppingBag,
  CalendarClock,
  PackageCheck,
  AlertTriangle,
  Wallet,
  Scissors,
  ArrowRight,
  RefreshCw,
  MessageCircle,
  Loader2,
  FileStack,
  CheckCircle2,
  IndianRupee,
} from 'lucide-react'
import { formatINR } from '@/lib/utils'
import { MetricCard } from '@/components/ui/metric-card'
import { PageHeader } from '@/components/ui/page-header'
import { ShortcutKey } from '@/components/shortcuts/ShortcutKey'
import { useShortcutsEnabled } from '@/lib/shortcuts/useKeyboardShortcuts'
import { useFrontDeskDrafts } from '@/lib/api/hooks/useFrontDeskDrafts'
import {
  useFrontDeskDashboard,
  type ActiveAlterationRow,
  type DueTodayRow,
  type PendingBalanceRow,
  type ReadyPickupRow,
} from '@/lib/api/hooks/useFrontDeskDashboard'

const QUICK_ACTIONS: { label: string; href: string; icon: React.ElementType; primary?: boolean; shortcut?: string[] }[] = [
  { label: 'New Main Order', href: '/front-desk/new?new=1', icon: FilePlus2, primary: true, shortcut: ['Alt', '1'] },
  { label: 'New Customer', href: '/front-desk/new?new=1', icon: UserPlus },
  { label: 'Search Customer', href: '/customers', icon: Search },
  { label: 'Order Status Lookup', href: '/front-desk/lookup', icon: Search, shortcut: ['Alt', '3'] },
  { label: 'Ready Rack Search', href: '/front-desk/ready-rack', icon: PackageCheck, shortcut: ['Alt', '4'] },
  { label: 'Alteration Requests', href: '/front-desk/alterations', icon: Scissors, shortcut: ['Alt', '5'] },
  { label: 'Saved Drafts', href: '/front-desk/drafts', icon: FileStack, shortcut: ['Alt', '2'] },
]

export function FrontDeskDashboard() {
  const { data, isLoading, isFetching, isError, error, refetch } = useFrontDeskDashboard()
  const { data: drafts } = useFrontDeskDrafts()
  const shortcutsEnabled = useShortcutsEnabled()
  const err = error as { message?: string; request_id?: string; code?: string } | null

  return (
    <div className="space-y-6">
      <PageHeader
        title="Front Desk"
        description="Today's counter operations"
        actions={
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => refetch()}
              disabled={isFetching}
              className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border-mid)] px-3 h-9 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] disabled:opacity-50 transition-colors"
            >
              {isFetching ? <Loader2 size={14} className="animate-spin" /> : <RefreshCw size={14} strokeWidth={1.75} />}
              Refresh
            </button>
          </div>
        }
      />

      {/* Quick actions */}
      <div className="flex flex-wrap gap-2">
        {QUICK_ACTIONS.map((a) => (
          <Link
            key={a.label}
            href={a.href}
            className={
              a.primary
                ? 'inline-flex items-center gap-2 rounded-xl bg-[var(--color-brand)] px-4 h-10 text-sm font-semibold text-white hover:bg-[var(--color-brand-dark)] transition-colors'
                : 'inline-flex items-center gap-2 rounded-xl border border-[var(--color-border-mid)] bg-white px-4 h-10 text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)] transition-colors'
            }
          >
            <a.icon size={16} strokeWidth={1.75} />
            {a.label}
            {a.shortcut && (
              <ShortcutKey
                keys={a.shortcut}
                muted={!shortcutsEnabled}
                className={a.primary ? 'ml-1 [&_kbd]:bg-white/15 [&_kbd]:border-white/30 [&_kbd]:text-white' : 'ml-1'}
              />
            )}
          </Link>
        ))}
      </div>

      {drafts && drafts.length > 0 && (
        <section className="rounded-xl border border-amber-200 bg-amber-50 p-4">
          <div className="mb-2 flex items-center justify-between">
            <h2 className="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-900">
              <PlayCircle size={15} strokeWidth={1.75} /> Paused drafts ({drafts.length})
            </h2>
            <Link href="/front-desk/drafts" className="inline-flex items-center gap-1 text-xs font-medium text-[var(--color-brand-dark)] hover:underline">
              View all <ArrowRight size={12} strokeWidth={2} />
            </Link>
          </div>
          <div className="space-y-1.5">
            {drafts.slice(0, 5).map((d) => (
              <div key={d.id} className="flex items-center gap-2 rounded-lg bg-white/70 px-3 py-2">
                <span className="min-w-0 flex-1 truncate text-sm text-[var(--color-text-primary)]">
                  {d.title ?? d.customer_name ?? 'Untitled draft'}
                  <span className="text-xs text-[var(--color-text-muted)]"> · {d.completed_count}/{d.total_items} complete</span>
                </span>
                <Link
                  href={`/front-desk/new?draft=${d.id}`}
                  className="inline-flex items-center gap-1 rounded-lg bg-[var(--color-brand)] px-3 h-8 text-xs font-medium text-white hover:bg-[var(--color-brand-dark)] transition-colors"
                >
                  Resume <ArrowRight size={12} strokeWidth={2} />
                </Link>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Loading / error / empty states */}
      {isLoading && (
        <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4">
          {[...Array(5)].map((_, i) => (
            <div key={i} className="h-24 rounded-xl ss-shimmer" style={{ opacity: 1 - i * 0.13 }} />
          ))}
        </div>
      )}

      {isError && (
        <div className="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          <AlertTriangle size={16} strokeWidth={1.75} className="mt-0.5 shrink-0" />
          <div className="flex-1">
            <p>{err?.code === 'UNAUTHENTICATED' ? 'Session expired — please sign in again.' : (err?.message ?? 'Could not load the dashboard.')}</p>
            {err?.request_id && <p className="text-xs opacity-75">request_id: {err.request_id}</p>}
          </div>
          <button onClick={() => refetch()} className="rounded-lg border border-red-300 px-2.5 h-8 text-xs font-medium hover:bg-red-100">
            Retry
          </button>
        </div>
      )}

      {data && (
        <>
          {/* Today */}
          <Section title="Today">
            <MetricCard label="New Orders Today" value={data.today.new_orders_count} icon={ShoppingBag} trend={`${data.today.confirmed_orders_count} confirmed`} trendDir="flat" />
            <MetricCard label="Due Today" value={data.today.due_today_count} icon={CalendarClock} trend="Delivery due" trendDir="flat" variant={data.today.due_today_count > 0 ? 'warning' : 'default'} />
            <MetricCard label="Overdue" value={data.today.overdue_count} icon={AlertTriangle} trend="Past delivery date" trendDir={data.today.overdue_count > 0 ? 'down' : 'flat'} variant={data.today.overdue_count > 0 ? 'danger' : 'default'} />
            <MetricCard label="Intake Preparation" value={data.today.intake_preparation_count} icon={FileStack} trend="Not yet confirmed" trendDir="flat" variant={data.today.intake_preparation_count > 0 ? 'warning' : 'default'} />
          </Section>

          {/* Pickup */}
          <Section title="Ready for pickup">
            <MetricCard label="Ready for Pickup" value={data.pickup.ready_for_pickup_count} icon={PackageCheck} trend="On the rack" trendDir="flat" variant={data.pickup.ready_for_pickup_count > 0 ? 'positive' : 'default'} />
            <MetricCard label="Ready · Balance Pending" value={data.pickup.ready_with_balance_pending_count} icon={Wallet} trend="Collect before handover" trendDir="flat" variant={data.pickup.ready_with_balance_pending_count > 0 ? 'warning' : 'default'} />
            <MetricCard label="Ready · Fully Paid" value={data.pickup.ready_fully_paid_count} icon={CheckCircle2} trend="OK to hand over" trendDir="flat" variant="positive" />
          </Section>

          {/* Payments (counter-operational only) */}
          <Section title="Payments">
            <MetricCard label="Pending Balance" value={formatINR(data.payments.pending_balance_amount)} icon={IndianRupee} trend={`${data.payments.pending_balance_orders_count} orders`} trendDir="flat" variant={data.payments.pending_balance_amount > 0 ? 'warning' : 'default'} />
            <MetricCard label="Collected Today" value={formatINR(data.payments.payments_collected_today)} icon={Wallet} trend="Counter collection" trendDir="up" variant="positive" />
          </Section>

          {/* Alterations + WhatsApp */}
          <Section title="Alterations &amp; messages">
            <MetricCard label="Active Alterations" value={data.alterations.active_count} icon={Scissors} trend={`${data.alterations.ready_count} ready`} trendDir="flat" variant={data.alterations.active_count > 0 ? 'warning' : 'default'} />
            <MetricCard label="Alterations · Intake" value={data.alterations.intake_count} icon={Scissors} trend="Awaiting start" trendDir="flat" />
            <MetricCard label="WhatsApp Failed" value={data.notifications.whatsapp_failed_count} icon={MessageCircle} trend="Today" trendDir="flat" variant={data.notifications.whatsapp_failed_count > 0 ? 'danger' : 'default'} />
            <MetricCard label="WhatsApp Simulated" value={data.notifications.whatsapp_simulated_today_count} icon={MessageCircle} trend="Today · no provider" trendDir="flat" />
          </Section>

          {/* Attention lists */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <ListCard title="Due today" icon={CalendarClock} empty="Nothing due today.">
              {data.quick_lists.due_today.map((r) => (
                <DueRow key={r.order_id} row={r} />
              ))}
            </ListCard>
            <ListCard title="Ready for pickup" icon={PackageCheck} empty="No orders are ready for pickup.">
              {data.quick_lists.ready_for_pickup.map((r) => (
                <ReadyRow key={r.order_id} row={r} />
              ))}
            </ListCard>
            <ListCard title="Pending balance" icon={Wallet} empty="No pending balances.">
              {data.quick_lists.pending_balance.map((r) => (
                <BalanceRow key={r.order_id} row={r} />
              ))}
            </ListCard>
            <ListCard title="Active alterations" icon={Scissors} empty="No active alterations.">
              {data.quick_lists.active_alterations.map((r) => (
                <AlterationRow key={r.alteration_id} row={r} />
              ))}
            </ListCard>
          </div>
        </>
      )}
    </div>
  )
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-3">
      <h2 className="text-xs font-semibold uppercase tracking-[0.08em] text-[var(--color-text-muted)]">{title}</h2>
      <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4">{children}</div>
    </section>
  )
}

function ListCard({ title, icon: Icon, empty, children }: { title: string; icon: React.ElementType; empty: string; children: React.ReactNode }) {
  const rows = Array.isArray(children) ? children : [children]
  const hasRows = rows.some(Boolean) && (children as React.ReactNode[]).length > 0
  return (
    <section className="rounded-xl border border-[var(--color-border)] bg-white p-4">
      <h3 className="mb-3 inline-flex items-center gap-1.5 text-sm font-semibold text-[var(--color-text-primary)]">
        <Icon size={15} strokeWidth={1.75} className="text-[var(--color-brand)]" /> {title}
      </h3>
      {hasRows ? <div className="space-y-2">{children}</div> : <p className="py-4 text-center text-xs text-[var(--color-text-muted)]">{empty}</p>}
    </section>
  )
}

function Who({ name, phone, masked }: { name: string | null; phone: string | null; masked: string | null }) {
  return (
    <p className="text-sm font-medium text-[var(--color-text-primary)] truncate">
      {name ?? '—'} <span className="text-xs font-normal text-[var(--color-text-muted)]">{phone ?? masked ?? ''}</span>
    </p>
  )
}

function OpenLink({ id, label = 'Open' }: { id?: number; label?: string }) {
  return (
    <Link href={`/orders/${id}`} className="inline-flex items-center gap-1 rounded-lg border border-[var(--color-border-mid)] px-2.5 h-7 text-[11px] font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
      {label} <ArrowRight size={11} strokeWidth={2} />
    </Link>
  )
}

function BalancePill({ amount }: { amount: number }) {
  return amount > 0 ? (
    <span className="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-[var(--color-warning)]">Balance {formatINR(amount)}</span>
  ) : (
    <span className="rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-medium text-[var(--color-success)]">Paid</span>
  )
}

function Row({ children }: { children: React.ReactNode }) {
  return <div className="flex items-center gap-2 rounded-lg border border-[var(--color-border)] px-3 py-2">{children}</div>
}

function DueRow({ row }: { row: DueTodayRow }) {
  return (
    <Row>
      <div className="min-w-0 flex-1">
        <span className="ss-mono text-xs text-[var(--color-text-secondary)]">{row.order_code}</span>
        <Who name={row.customer_name} phone={row.phone} masked={row.phone_masked} />
      </div>
      <BalancePill amount={row.balance_amount} />
      <OpenLink id={row.order_id} label={row.balance_amount > 0 ? 'Collect' : 'Open'} />
    </Row>
  )
}

function ReadyRow({ row }: { row: ReadyPickupRow }) {
  return (
    <Row>
      <div className="min-w-0 flex-1">
        <span className="ss-mono text-xs text-[var(--color-text-secondary)]">{row.order_code}</span>
        {row.rack_slots.length > 0 && (
          <span className="ml-1.5 rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-700">Rack {row.rack_slots.join(', ')}</span>
        )}
        <Who name={row.customer_name} phone={row.phone} masked={row.phone_masked} />
      </div>
      <BalancePill amount={row.balance_amount} />
      <OpenLink id={row.order_id} label={row.payment_status === 'paid' ? 'Handover' : 'Collect'} />
    </Row>
  )
}

function BalanceRow({ row }: { row: PendingBalanceRow }) {
  return (
    <Row>
      <div className="min-w-0 flex-1">
        <span className="ss-mono text-xs text-[var(--color-text-secondary)]">{row.order_code}</span>
        <Who name={row.customer_name} phone={row.phone} masked={row.phone_masked} />
      </div>
      <BalancePill amount={row.balance_amount} />
      <OpenLink id={row.order_id} label="Collect" />
    </Row>
  )
}

function AlterationRow({ row }: { row: ActiveAlterationRow }) {
  return (
    <Row>
      <div className="min-w-0 flex-1">
        <span className="ss-mono text-xs text-[var(--color-text-secondary)]">{row.order_code ?? '—'}</span>
        <Who name={row.customer_name} phone={row.phone} masked={row.phone_masked} />
      </div>
      <span className="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800">{row.status}</span>
      {row.priority === 'urgent' && <span className="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-600">Urgent</span>}
      <Link href={`/front-desk/alterations/${row.alteration_id}`} className="inline-flex items-center gap-1 rounded-lg border border-[var(--color-border-mid)] px-2.5 h-7 text-[11px] font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]">
        Open <ArrowRight size={11} strokeWidth={2} />
      </Link>
    </Row>
  )
}
