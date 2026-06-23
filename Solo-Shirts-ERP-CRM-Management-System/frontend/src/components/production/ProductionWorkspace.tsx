'use client'

import { useState } from 'react'
import { LayoutGrid, Columns3 } from 'lucide-react'
import { KanbanBoard } from './KanbanBoard'
import { StationsView } from './StationsView'
import { cn } from '@/lib/utils'

type View = 'stations' | 'board'

function tabCls(active: boolean): string {
  return cn(
    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md font-medium transition-colors',
    active
      ? 'bg-[var(--color-brand)] text-white'
      : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-alt)]',
  )
}

/**
 * Hosts the two production layouts behind a toggle:
 *   • Stations — pick a stage, see only its cards (simple, no horizontal scroll)
 *   • Board    — the full horizontal Kanban (all stages side by side)
 * Defaults to Stations, the floor-friendly view.
 */
export function ProductionWorkspace() {
  const [view, setView] = useState<View>('stations')

  return (
    <div className="space-y-4">
      <div className="inline-flex rounded-lg border border-[var(--color-border-mid)] p-0.5 text-xs">
        <button onClick={() => setView('stations')} className={tabCls(view === 'stations')}>
          <LayoutGrid size={13} strokeWidth={1.75} /> Stations
        </button>
        <button onClick={() => setView('board')} className={tabCls(view === 'board')}>
          <Columns3 size={13} strokeWidth={1.75} /> Board
        </button>
      </div>

      {view === 'stations' ? <StationsView /> : <KanbanBoard />}
    </div>
  )
}
